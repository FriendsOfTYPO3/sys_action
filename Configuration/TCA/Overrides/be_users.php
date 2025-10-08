<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'be_users',
    [
        'createdByAction' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        // The owner of the be_user
        'cruser_id' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ]
);

// We set this configuration so the disable field is not added to "excludedTablesAndFields" in DataHandler::start
// Otherwise, a non-admin user couldn't disable/enable a user through this action
$GLOBALS['TCA']['be_users']['columns']['disable']['exclude'] = 0;
