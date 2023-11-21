<?php

namespace App\Services;

use PDO;
use App\Database\DB;
use App\Models\PushNotification;

class NotificationService
{
    public static function getDetail(DB $db, $notificationId)
    {
        $thisClass = new self();
        $notification = $db->getById('notifications', $notificationId);

        if (!$notification) {
            return false;
        }

        $queues = $thisClass->eagerLoadQueues($db, [$notification]);

        return [
            'id'      => $notification['id'],
            'title'   => $notification['title'],
            'message' => $notification['message'],
            ...$thisClass->getTotalStatuses($queues)
        ];
    }

    public static function sendNotifications(DB $db)
    {
        $thisClass = new self();

        // get notifications that are not done yet
        $notifications = $db->getPdo()->prepare('SELECT * FROM notifications WHERE queue_status = 0');
        $notifications->execute();
        $notifications = $notifications->fetchAll(PDO::FETCH_ASSOC);

        if (!$notifications) {
            return false;
        }

        foreach ($notifications as $notification) {
            $queueIds = $thisClass->generateQueuesIfDoesntExist($db, $notification['country_id'], $notification['id']);

            $queues = $db->getByIds('queues', $queueIds, 'id');

            foreach ($queues as $queue) {
                $device = $db->getById('devices', $queue['device_id']);

                if (!$device) {
                    continue;
                }

                $data = [
                    'is_in_queue' => 0,
                    'status'      => (int)PushNotification::send($notification['title'], $notification['message'], $device['token']),
                ];

                $db->update('queues', $queue['id'], $data);
            }

            $db->update('notifications', $notification['id'], ['queue_status' => 1]);
        }

        $notifications = $db->getByIds('notifications', array_column($notifications, 'id'));

        $data = [];

        foreach ($notifications as $notification) {
            $queues = $db->getByIds('queues', [$notification['id']], 'notification_id');

            $data[] = [
                'notification_id' => $notification['id'],
                'title'           => $notification['title'],
                'message'         => $notification['message'],
                ...$thisClass->getTotalStatuses($queues)
            ];
        }

        return $data;
    }

    private function generateQueuesIfDoesntExist(DB $db, $countryId, $notificationId)
    {
        $queueStmt = $db->getPdo()->prepare("SELECT * FROM queues WHERE notification_id = :notification_id LIMIT 1");
        $queueStmt->execute(['notification_id' => $notificationId]);
        $queue = $queueStmt->fetch(PDO::FETCH_ASSOC);

        if ($queue) {
            return [];
        }

        $users = $this->getUsersByCountryId($db, $countryId);
        $userIds = array_column($users, 'id');

        $devices = $this->getActiveDevicesByUserIds($db, $userIds);
        $deviceIds = array_column($devices, 'id');

        $ids = [];

        for ($i = 0; $i < 100000; $i++) {
            $db->insert('queues', [
                'notification_id' => $notificationId,
                'device_id'       => $deviceIds[array_rand($deviceIds)],
                'is_in_queue'     => 1,
            ]);
            $ids[] = $db->pdo->lastInsertId();
        }

        return $ids;
    }

    private function eagerLoadQueues(DB $db, $notifications): array
    {
        if (!$notifications) return [];

        $notificationIds = array_column($notifications, 'id');

        // eager load queues
        $queues = $db->getByIds('queues', $notificationIds, 'notification_id');

        $groupedQueues = [];

        foreach ($queues as $queue) {
            $groupedQueues[$queue['notification_id']][] = $queue;
        }

        if (count($notifications) === 1) {
            $groupedQueues = $groupedQueues[$notificationIds[0]] ?? [];
        }

        return $groupedQueues;
    }

    private function getUsersByCountryId($db, $countryId)
    {
        $usersByCountryIdStmt = $db->getPdo()->prepare("SELECT * FROM users WHERE country_id = :country_id");
        $usersByCountryIdStmt->execute(['country_id' => $countryId]);

        return $usersByCountryIdStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getActiveDevicesByUserIds($db, $userIds)
    {
        $placeholders = str_repeat('?, ', count($userIds) - 1).'?';
        $activeDevicesByUserIdsStmt = $db->getPdo()->prepare("SELECT * FROM devices WHERE user_id IN ($placeholders) AND expired = 0");
        $activeDevicesByUserIdsStmt->execute($userIds);

        return $activeDevicesByUserIdsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTotalStatuses($queues): array
    {
        $sentQueues = array_filter($queues, function ($queue) {
            return $queue['status'] === 1;
        });
        $failedQueues = array_filter($queues, function ($queue) {
            return $queue['status'] === 0;
        });
        $inProgressQueues = array_filter($queues, function ($queue) {
            return $queue['is_in_progress'] === 1;
        });
        $inQueueQueues = array_filter($queues, function ($queue) {
            return $queue['is_in_queue'] === 1;
        });

        return [
            'sent'        => count($sentQueues),
            'failed'      => count($failedQueues),
            'in_progress' => count($inProgressQueues),
            'in_queue'    => count($inQueueQueues),
        ];
    }
}
