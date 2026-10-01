"use strict";

const sqlite3 = require("/app/node_modules/sqlite3").verbose();

const databasePath = "/app/database/database.sqlite";
const spaceId = "omo-local-demo";
const spaceName = "Tableau blanc de demonstration OMO";
const spaceAuth = "omo-local-demo";
const timeoutAt = Date.now() + 30000;

function waitForSpacesTable(database) {
    return new Promise((resolve, reject) => {
        const check = () => {
            database.get(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'spaces'",
                (error, row) => {
                    if (error) {
                        reject(error);
                        return;
                    }

                    if (row) {
                        resolve();
                        return;
                    }

                    if (Date.now() >= timeoutAt) {
                        reject(new Error("SpaceDeck did not initialize its SQLite schema in time."));
                        return;
                    }

                    setTimeout(check, 250);
                }
            );
        };

        check();
    });
}

function ensureDemoSpace(database) {
    return new Promise((resolve, reject) => {
        const now = new Date().toISOString();
        database.run(
            "INSERT OR IGNORE INTO spaces (_id, name, space_type, access_mode, edit_hash, edit_slug, width, height, background_color, created_at, updated_at, createdAt, updatedAt) VALUES (?, ?, 'space', 'private', ?, ?, 1600, 900, '#ffffff', ?, ?, ?, ?)",
            [spaceId, spaceName, spaceAuth, spaceId, now, now, now, now],
            (error) => {
                if (error) {
                    reject(error);
                    return;
                }

                resolve();
            }
        );
    });
}

async function main() {
    const database = new sqlite3.Database(databasePath);

    try {
        await waitForSpacesTable(database);
        await ensureDemoSpace(database);
        console.log("Local OMO demo whiteboard is ready.");
    } finally {
        database.close();
    }
}

main().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
