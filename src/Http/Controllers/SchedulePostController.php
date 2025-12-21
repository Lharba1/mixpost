<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Inovector\Mixpost\Actions\PublishPost;
use Inovector\Mixpost\Facades\Settings;
use Inovector\Mixpost\Http\Requests\SchedulePost;
use Inovector\Mixpost\Util;

class SchedulePostController extends Controller
{
    public function __invoke(SchedulePost $schedulePost): JsonResponse
    {
        $schedulePost->handle();

        // If postNow is true, immediately publish instead of waiting for cron
        if ($schedulePost->input('postNow')) {
            (new PublishPost())($schedulePost->post);
            
            return response()->json("Your post is being published now!");
        }

        $scheduledAt = $schedulePost->getDateTime()->tz(Settings::get('timezone'))->format("D, M j, " . Util::timeFormat());

        return response()->json("The post has been scheduled.\n$scheduledAt");
    }
}
