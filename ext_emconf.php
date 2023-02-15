<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Task Center Actions',
    'description' => 'Actions are \'programmed\' admin tasks which can be performed by selected regular users from the Task Center. An action could be creation of backend users, fixed SQL SELECT queries, listing of records, direct edit access to selected records etc.',
    'category' => 'module',
    'state' => 'stable',
    'author' => 'Friends of TYPO3',
    'author_email' => 'friendsof@typo3.org',
    'author_company' => '',
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.23-11.9.99',
            'taskcenter' => '11.5.23-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
