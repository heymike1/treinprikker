<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminFeedbackController extends Controller
{
    public function index(): View
    {
        return view('admin.feedback', [
            'feedback' => Feedback::latest()->paginate(30),
            'unread' => Feedback::whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Feedback $feedback): RedirectResponse
    {
        $feedback->update(['read_at' => $feedback->read_at ? null : now()]);

        return back();
    }
}
