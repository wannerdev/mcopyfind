<?php

$capabilities = array(
    'plagiarism/mcopyfind:create'  => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
        ],
    ),
    'plagiarism/mcopyfind:view' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
        ],
    ), 
);
