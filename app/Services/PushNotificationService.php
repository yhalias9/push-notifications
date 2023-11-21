<?php

namespace App\Services;

class PushNotificationService
{
    private string $notificationId;

    public $notification;

    public int $sentTotal;
    public int $failedTotal;
    public int $inProgressTotal;
    public int $queuedTotal;

    public function __construct($notificationId)
    {
        $this->notificationId = $notificationId;
    }
}