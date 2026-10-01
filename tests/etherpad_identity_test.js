'use strict';

// Run inside the local Etherpad container. Only a temporary test pad is modified.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {io} = require('/opt/etherpad-lite/src/node_modules/socket.io-client');
const apiKey = fs.readFileSync('/opt/etherpad-lite/APIKEY.txt', 'utf8').trim();
const api = async (method, parameters = {}) => {
  const query = new URLSearchParams({apikey: apiKey, ...parameters});
  const result = await (await fetch(`http://127.0.0.1:9001/api/1.3.0/${method}?${query}`)).json();
  assert.equal(result.code, 0, `${method}: ${result.message}`);
  return result.data;
};
const waitMessage = (socket, matches) => new Promise((resolve, reject) => {
  const receive = (message) => {
    if (!matches(message)) return;
    clearTimeout(timeout);
    socket.off('message', receive);
    resolve(message);
  };
  const timeout = setTimeout(() => {
    socket.off('message', receive);
    reject(new Error('Expected pad message timed out'));
  }, 10000);
  socket.on('message', receive);
});
const connect = (padID, sessionID, name) => {
  const socket = io('http://127.0.0.1:9001', {query: {padId: padID}, reconnection: false});
  socket.on('connect', () => socket.emit('message', {
    component: 'pad', type: 'CLIENT_READY', padId: padID, sessionID,
    token: 't.omoIdentityRegressionTest', userInfo: {name, colorId: '#112233'},
  }));
  return socket;
};

(async () => {
  const {groupID} = await api('createGroup');
  let socket;
  let observer;
  try {
    const {padID} = await api('createGroupPad', {groupID, padName: 'identity-regression'});
    const {authorID: observerID} = await api('createAuthor', {name: 'Test observer'});
    const {sessionID: observerSession} = await api('createSession', {groupID, authorID: observerID, validUntil: Math.floor(Date.now() / 1000) + 60});
    observer = connect(padID, observerSession, 'Test observer');
    await waitMessage(observer, (message) => message.type === 'CLIENT_VARS');
    // Members keep their name alone; external authors keep their confirmed email.
    for (const name of ['OMO Test Member', 'OMO Test Guest (identity-test@example.invalid)']) {
      const {authorID} = await api('createAuthor', {name});
      const {sessionID} = await api('createSession', {groupID, authorID, validUntil: Math.floor(Date.now() / 1000) + 60});
      socket = connect(padID, sessionID, 'Impersonated member');
      const {data: clientVars} = await waitMessage(socket, (message) => message.type === 'CLIENT_VARS');
      assert.equal(clientVars.omoIdentityLocked, true);
      assert.equal(await api('getAuthorName', {authorID}), name, 'Name must survive a forged handshake');
      const update = waitMessage(observer, (message) => message.data?.type === 'USER_NEWINFO'
        && message.data.userInfo.userId === authorID && message.data.userInfo.colorId === '#abcdef');
      socket.emit('message', {
        component: 'pad', type: 'COLLABROOM',
        data: {type: 'USERINFO_UPDATE', userInfo: {name: 'Another member', colorId: '#abcdef'}},
      });
      assert.equal((await update).data.userInfo.name, name, 'Other users must see the canonical name after a forged rename');
      socket.disconnect();
      socket = null;
      await api('deleteSession', {sessionID});
    }
    console.log('Member and external identities: forged handshake and rename blocked.');
  } finally {
    if (socket) socket.disconnect();
    if (observer) observer.disconnect();
    await api('deleteGroup', {groupID});
  }
})().catch((error) => { console.error(error.message); process.exitCode = 1; });
