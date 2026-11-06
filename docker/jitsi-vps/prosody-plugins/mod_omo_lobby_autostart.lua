-- Signed room defaults and per-role permissions. Old JWTs keep the default lobby.
-- stable-11248-3 listens to create-lobby-room. The bundled contrib autostart
-- plugin still emits create-persistent-lobby-room, which that release ignores.
local util = module:require "util";
local st = require "util.stanza";
local crypto = require "util.crypto";
local hashes = require "util.hashes";
local base64 = require "util.encodings".base64;
local base_host = module:get_option_string("muc_mapper_domain_base", "meet.jitsi");
local av_host = "avmoderation." .. base_host;

local function native_password(room, sealed)
    if sealed == nil or sealed == "" then return nil, true; end
    if type(sealed) ~= "string" or sealed:sub(1,3) ~= "v1:" then return nil, false; end
    local secret = module:context(base_host):get_option_string("app_secret", "");
    local packed = base64.decode(sealed:sub(4));
    if #secret < 32 or not packed or #packed < 29 then return nil, false; end
    local name = room.jid:match("^([^@]+)@");
    local key = hashes.sha256("omo-jitsi-room-password-v1:" .. secret .. ":" .. name);
    local ok, password = pcall(crypto.aes_256_gcm_decrypt, key, packed:sub(1,12), packed:sub(13));
    return password, ok and type(password) == "string" and #password > 0;
end

local function role(session)
    local user = session.jitsi_meet_context_user or {};
    if user.moderator == true or user.moderator == "true" then return "owner"; end
    return user.access_role == "member" and "member" or "guest";
end

local function av_module()
    return prosody.hosts[av_host] and prosody.hosts[av_host].modules.av_moderation_component;
end

local function ensure_lobby(room, enabled)
    if enabled and not room._data.lobbyroom then
        prosody.events.fire_event("create-lobby-room", { room = room; });
    elseif not enabled and room._data.lobbyroom then
        room:set_members_only(false);
        prosody.events.fire_event("destroy-lobby-room", { room = room; newjid = room.jid; });
    end
end

local function approve(room, occupant, access_role)
    local policy = room._data.omo_policy;
    if not policy then return; end
    for media,field in pairs({video = "camera_restricted", desktop = "screen_restricted"}) do
        local whitelist = room.av_moderation and room.av_moderation[media];
        if whitelist and (access_role == "owner" or not policy[access_role][field]) then
            local nick = util.internal_room_jid_match_rewrite(occupant.nick);
            local found = false;
            for _,value in ipairs(whitelist) do if value == nick then found = true; break; end end
            if not found then whitelist:push(nick); end
        end
    end
end

-- Runs after token_verification (99), before affiliation/bypass/lobby gates.
module:hook("muc-occupant-pre-join", function(event)
    local room, session, occupant = event.room, event.origin, event.occupant;
    if util.is_healthcheck_room(room.jid) or util.is_admin(occupant.bare_jid) or not session.auth_token then return; end
    local context = session.jitsi_meet_context_room or {};
    local policy = context.omo_policy;
    local current = room._data.omo_policy;
    if type(policy) == "table" and type(policy.version) == "number" and type(policy.member) == "table" and type(policy.guest) == "table"
        and (not current or policy.version > current.version) then
        local av = av_module();
        if not av then
            session.send(st.error_reply(event.stanza, "cancel", "service-unavailable", "Room moderation is unavailable"));
            return true;
        end
        local password, valid = native_password(room, policy.native_password);
        if not valid then
            module:log("error", "Cannot initialize the encrypted native room password");
            session.send(st.error_reply(event.stanza, "cancel", "service-unavailable", "Room password configuration is unavailable"));
            return true;
        end
        room:set_password((policy.member.lobby or policy.guest.lobby) and password or nil);
        room._data.omo_policy = policy;
        room._data.omo_roles = room._data.omo_roles or {};
        room.jitsiMetadata = room.jitsiMetadata or {};
        ensure_lobby(room, policy.member.lobby or policy.guest.lobby);
        for media,field in pairs({video = "camera_restricted", desktop = "screen_restricted"}) do
            if policy.member[field] or policy.guest[field] then
                av.start_av_moderation(room, media, {nick = room.jid .. "/focus"});
                -- Per-person startup is carried by the signed access and iframe config.
                -- A room-wide startMuted would also mute the unrestricted group.
                room.jitsiMetadata.startMuted[media] = nil;
                for _,present in room:each_occupant() do
                    approve(room, present, room._data.omo_roles[present.bare_jid] or (present.role == "moderator" and "owner" or "guest"));
                end
                av.notify_occupants_enable(nil, true, room, room.jid .. "/focus", media);
                for _,present in room:each_occupant() do
                    local nick = util.internal_room_jid_match_rewrite(present.nick);
                    for _,allowed in ipairs(room.av_moderation[media]) do
                        if allowed == nick then av.notify_whitelist_change(present.jid, false, room, media); break; end
                    end
                end
            elseif room.av_moderation and room.av_moderation[media] then
                room.av_moderation[media] = nil;
                room.av_moderation_actors[media] = nil;
                room.jitsiMetadata.startMuted[media] = nil;
                av.notify_occupants_enable(nil, false, room, room.jid .. "/focus", media);
            end
        end
    end
    if room._data.omo_policy then
        room._data.omo_roles[occupant.bare_jid] = role(session);
        approve(room, occupant, role(session));
    end
end, 90);

-- Reuse Jitsi's A/V moderation protocol and individual approval UI.
module:hook("muc-occupant-joined", function(event)
    local room = event.room;
    if not room._data.omo_policy then return; end
    local av = av_module();
    if not av then return; end
    for _,media in ipairs({"video", "desktop"}) do
        local whitelist = room.av_moderation and room.av_moderation[media];
        if whitelist then
            local target;
            local nick = util.internal_room_jid_match_rewrite(event.occupant.nick);
            for _,value in ipairs(whitelist) do if value == nick then target = event.occupant.jid; break; end end
            av.notify_whitelist_change(target, true, room, media);
        end
    end
end, -3);

module:hook("muc-room-pre-create", function(event)
    if util.is_healthcheck_room(event.room.jid) then
        return;
    end
    prosody.events.fire_event("create-lobby-room", { room = event.room; });
end);
