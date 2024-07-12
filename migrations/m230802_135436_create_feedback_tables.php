<?php

use yii\db\Migration;

/**
 * Class m230802_135436_create_feedback_tables
 */
class m230802_135436_create_feedback_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('feedback', [
            'id' => $this->primaryKey(),
            'election_references' => $this->string(255)->notNull(),
            'input_field' => $this->string(255)->notNull(),
            'user_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex('fk_feedback_user1_idx', 'feedback', 'user_id');
        $this->addForeignKey(
            'fk_feedback_user1',
            'feedback',
            'user_id',
            'user',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->createTable('feedback_attachment', [
            'id' => $this->primaryKey(),
            'type' => $this->string(255)->notNull(),
            'file' => $this->string(255)->notNull(),
        ]);

        $this->createTable('feedback_attachment_has_feedback', [
            'id' => $this->primaryKey(),
            'feedback_attachment_id' => $this->integer()->notNull(),
            'feedback_id' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'fk_feedback_attachment_has_feedback_feedback1_idx',
            'feedback_attachment_has_feedback',
            'feedback_id'
        );
        $this->createIndex(
            'fk_feedback_attachment_has_feedback_feedback_attachment1_idx',
            'feedback_attachment_has_feedback',
            'feedback_attachment_id'
        );
        $this->addForeignKey(
            'fk_feedback_attachment_has_feedback_feedback1',
            'feedback_attachment_has_feedback',
            'feedback_id',
            'feedback',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
        $this->addForeignKey(
            'fk_feedback_attachment_has_feedback_feedback_attachment1',
            'feedback_attachment_has_feedback',
            'feedback_attachment_id',
            'feedback_attachment',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }
}
