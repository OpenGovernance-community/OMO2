'use strict';

const authors = require('ep_etherpad-lite/node/db/AuthorManager');
const padMessages = require('ep_etherpad-lite/node/handler/PadMessageHandler');

const authenticatedSession = (socket) => {
  const session = padMessages.sessioninfos[socket.id];
  return session && session.auth && session.auth.sessionID && session.author ? session : null;
};

// These hooks run after Etherpad validates the session and binds it to an author.
// Names supplied by OMO's API must never be replaced by browser-controlled values.
exports.handleMessage = async (hookName, context) => {
  const message = context.message;
  const ready = message.type === 'CLIENT_READY';
  const update = message.type === 'COLLABROOM' && message.data && message.data.type === 'USERINFO_UPDATE';
  if (!ready && !update) return [];
  const session = authenticatedSession(context.socket);
  if (!session) return [];
  const author = await authors.getAuthor(session.author);
  const info = ready ? (message.userInfo ||= {}) : message.data.userInfo;
  if (info) info.name = author.name || '';
  return [];
};

exports.clientVars = async (hookName, context) => ({omoIdentityLocked: !!authenticatedSession(context.socket)});
