<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\StoreRequest;
use App\Http\Requests\Message\UpdateRequest;
use App\Http\Resources\Message\MessageResource;
use App\Models\Image;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Str;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // output data id users for func answer for another users
        $ids = Str::of($data['content'])->matchAll('/@[\d]+/')->unique()->transform(
            function ($id) {
                return Str::of($id)->replaceMatches('/@/', '')->value();
        });

        $imgIds = Str::of($data['content'])->matchAll('/img_id=[\d]+/')->unique()->transform(
            function ($id) {
                return Str::of($id)->replaceMatches('/img_id=/', '')->value();
        });

        // dd($imgIds);

        $message = Message::create($data);

        // removing unnecessary images from the table storage
        Image::whereIn('id', $imgIds)->update([
            'message_id' => $message->id
        ]);

        Image::where('user_id', auth()->id())
            ->whereNull('message_id')
            ->get()
            ->pluck('path')
            ->each(function($path) {
                Storage::disk('public')->delete($path);
        });

        Image::where('user_id', auth()->id())
            ->whereNull('message_id')->delete();

        $message->answeredUsers()->attach($ids);

        $message->loadCount('likedUsers');

        return MessageResource::make($message)->resolve();
    }

    /**
     * Display the specified resource.
     */
    public function show(Message $message)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Message $message)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Message $message)
    {
        //
    }

    public function toggleLike(Message $message) {
        $message->likedUsers()->toggle(auth()->id());
    }

    public function storeComplaint(\App\Http\Requests\Complaint\StoreRequest $request, Message $message) {
        $data = $request->validated();
        $message->complaintedUsers()->attach(auth()->id(), $data);

        return MessageResource::make($message)->resolve();
    }
}
