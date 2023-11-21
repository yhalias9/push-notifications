<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Queues extends AbstractMigration
{
    public function up(): void
    {
        $this->table('queues')
            ->addColumn('notification_id', 'integer')
            ->addColumn('device_id', 'integer')
            ->addColumn('status', 'boolean', ['default' => 0])
            ->addColumn('is_in_progress', 'boolean', ['default' => 0])
            ->addColumn('is_in_queue', 'boolean', ['default' => 0])
            ->addForeignKey(
                'notification_id',
                'notifications',
                'id',
                [
                    'delete'=> 'CASCADE',
                    'constraint' => 'queue_notification_id',
                ]
            )
            ->addForeignKey(
                'device_id',
                'devices',
                'id',
                [
                    'delete'=> 'CASCADE',
                    'constraint' => 'queue_device_id',
                ]
            )
            ->create();
    }

    public function down(): void
    {
        $this->table('queues')
            ->drop();
    }
}
