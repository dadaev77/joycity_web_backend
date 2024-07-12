<?php
use yii\rbac\Item;

return [
	'web/backend/user' => [
			'type' => Item::TYPE_PERMISSION,
	],
	'web/backend/client' => [
			'type' => Item::TYPE_PERMISSION,
	],
	'web/backend/content' => [
			'type' => Item::TYPE_PERMISSION,
	],
	'guest' => [
			'type' => Item::TYPE_ROLE,
			'children' => [
					'web/backend',
			],
	],
	'editor' => [
			'type' => Item::TYPE_ROLE,
			'description' => 'Редактор',
			'children' => [
					'guest',
					'web/backend/content',
			],
	],
	'moderator' => [
			'type' => Item::TYPE_ROLE,
			'description' => 'Модератор',
			'children' => [
					'editor',
					'web/backend/client',
			],
	],
    'admin' => [
        'type' => Item::TYPE_ROLE,
        'description' => 'Администратор',
        'children' => [
        			'moderator',
        			'web/backend/user',
        ],
    ],
];
