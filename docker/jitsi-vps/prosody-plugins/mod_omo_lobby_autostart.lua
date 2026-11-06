-- Default lobby for every room; JWT roles and token_lobby_bypass control admission.
-- stable-11248-3 listens to create-lobby-room. The bundled contrib autostart
-- plugin still emits create-persistent-lobby-room, which that release ignores.
local util = module:require "util";

module:hook("muc-room-pre-create", function(event)
    if util.is_healthcheck_room(event.room.jid) then
        return;
    end
    prosody.events.fire_event("create-lobby-room", { room = event.room; });
end);
