<?php

declare(strict_types=1);

namespace Aurora\Module\Editorial\Post\Scheduler;

use Aurora\Core\Scheduler\RecurringMessageProviderInterface;
use Aurora\Module\Editorial\Post\Message\PublishScheduledPostsMessage;
use Aurora\Module\Editorial\Post\Message\PurgeTrashedPostsMessage;
use Symfony\Component\Scheduler\RecurringMessage;

/**
 * Editorial's recurring jobs, contributed to the core 'main' schedule.
 */
final class EditorialRecurringMessageProvider implements RecurringMessageProviderInterface
{
    public function getRecurringMessages(): iterable
    {
        yield RecurringMessage::cron('* * * * *', new PublishScheduledPostsMessage());
        yield RecurringMessage::cron('0 3 * * *', new PurgeTrashedPostsMessage());
    }
}
