<?php

namespace App\Http\Controllers;

use App\Game\CurrentPlayer;
use App\Models\Feedback;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function create(): View
    {
        return view('pages.feedback', ['categories' => Feedback::CATEGORIES]);
    }

    public function store(Request $request, CurrentPlayer $currentPlayer): RedirectResponse
    {
        // Honeypot: real visitors never fill this hidden field.
        if ($request->filled('website')) {
            return redirect()->route('feedback.create')->with('status', 'Bedankt voor je feedback!');
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'category' => ['required', Rule::in(array_keys(Feedback::CATEGORIES))],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'category.required' => 'Kies een soort feedback.',
            'category.in' => 'Kies een soort feedback.',
            'message.required' => 'Vertel ons wat je kwijt wilt.',
            'message.min' => 'Iets meer uitleg helpt ons: minimaal 10 tekens.',
            'message.max' => 'Houd het onder de 2000 tekens.',
            'name.max' => 'Je naam mag maximaal 80 tekens zijn.',
        ]);

        Feedback::create([
            'player_id' => $currentPlayer->find()?->id,
            'name' => $data['name'] ?: null,
            'category' => $data['category'],
            'message' => $data['message'],
            'page' => $request->input('page') ? substr((string) $request->input('page'), 0, 200) : null,
        ]);

        return redirect()->route('feedback.create')->with('status', 'Bedankt voor je feedback! We lezen alles.');
    }
}
