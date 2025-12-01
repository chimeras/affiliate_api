<?php
namespace App\Commands;

use App\Models\NotificationModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendQueuedEmails extends BaseCommand
{
    protected $group = 'Workers';
    protected $name = 'worker:emails';
    protected $description = 'Process queued notification emails.';

    public function run(array $params)
    {
        $model = new NotificationModel();
        $pending = $model->whereNull('read_at')->limit(50)->findAll();
        foreach ($pending as $notification) {
            // In production, send via mailer
            CLI::write("Sending email to user {$notification['user_id']} - {$notification['title']}");
            $model->update($notification['id'], ['read_at' => date('Y-m-d H:i:s')]);
        }
    }
}
