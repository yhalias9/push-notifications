<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Notifications extends AbstractMigration
{
    public function up(): void
    {
        $this->table('notifications')
            ->addColumn('title', 'string')
            ->addColumn('message', 'text')
            ->addColumn('country_id', 'integer')
            ->addColumn('queue_status', 'integer', ['default' => 0]) // 0 - not done, 1 - done
            ->addForeignKey(
                'country_id',
                'countries',
                'id',
                [
                    'delete'=> 'CASCADE',
                    'constraint' => 'notification_country_id',
                ]
            )
            ->create();
    }

    public function down(): void
    {
        $this->table('notifications')
            ->drop();
    }
}
