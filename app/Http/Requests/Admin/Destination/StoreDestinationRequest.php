<?php

namespace App\Http\Requests\Admin\Destination;

use App\Models\Destination;
use Illuminate\Foundation\Http\FormRequest;

class StoreDestinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Destination::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:wordpress_site,telegram_channel,telegram_group,bale_channel,bale_group'],
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:191'],

            'url' => ['required_if:type,wordpress_site', 'string', 'max:500'],
            'api_key' => ['required_if:type,wordpress_site', 'string'],

            'bot_id' => ['required_if:type,telegram_channel,telegram_group,bale_channel,bale_group', 'integer', 'exists:bots,id'],
            'external_chat_id' => ['required_if:type,telegram_channel,telegram_group,bale_channel,bale_group', 'string', 'max:191'],
        ];
    }
}
