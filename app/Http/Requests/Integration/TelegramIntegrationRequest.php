<?php

namespace App\Http\Requests\Integration;

class TelegramIntegrationRequest extends BaseIntegrationRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:telegram',
            'credentials' => 'required|array',
            'credentials.bot_token' => 'required|string|min:10',
            'credentials.chats' => 'required|array|min:1',
            'credentials.chats.*' => 'required|array',
            'credentials.chats.*.id' => 'required|integer',
            'credentials.chats.*.name' => 'nullable|string|max:255',
            'credentials.parse_mode' => 'nullable|string|in:HTML,Markdown',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'credentials.bot_token.required' => 'Bot token is required',
            'credentials.bot_token.min' => 'Bot token must be at least 10 characters',
            'credentials.chats.required' => 'Chats array is required',
            'credentials.chats.array' => 'Chats must be an array',
            'credentials.chats.min' => 'At least one chat is required',
            'credentials.chats.*.required' => 'Chat configuration is required',
            'credentials.chats.*.array' => 'Chat configuration must be an array',
            'credentials.chats.*.id.required' => 'Chat ID is required',
            'credentials.chats.*.id.integer' => 'Chat ID must be an integer',
            'credentials.chats.*.name.max' => 'Chat name cannot exceed 255 characters',
            'credentials.parse_mode.in' => 'Parse mode must be HTML or Markdown',
        ];
    }
}
