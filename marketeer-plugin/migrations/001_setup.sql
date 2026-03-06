-- Marketeer Plugin Migration 001: Initial Setup
-- Run per-user when the plugin is installed.
-- Uses generic plugin_data table for campaign/page storage (non-invasive).

INSERT IGNORE INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE)
VALUES (:userId, :group, 'enabled', '1');

INSERT IGNORE INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE)
VALUES (:userId, :group, 'default_language', 'en');

INSERT IGNORE INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE)
VALUES (:userId, :group, 'cta_url', 'https://web.synaplan.com');

INSERT IGNORE INTO BCONFIG (BOWNERID, BGROUP, BSETTING, BVALUE)
VALUES (:userId, :group, 'brand_name', 'Synaplan');
