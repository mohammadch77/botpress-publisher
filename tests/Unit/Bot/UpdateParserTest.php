<?php

use App\Services\Bot\UpdateParser;

beforeEach(function () {
    $this->parser = new UpdateParser;
});

it('parses a text message', function () {
    $update = $this->parser->parseTelegram([
        'update_id' => 1,
        'message' => [
            'message_id' => 10,
            'date' => 1700000000,
            'chat' => ['id' => 123, 'type' => 'private'],
            'from' => ['id' => 456, 'username' => 'john', 'first_name' => 'John'],
            'text' => 'hello',
        ],
    ]);

    expect($update->updateType)->toBe('message');
    expect($update->message->messageType)->toBe('text');
    expect($update->message->text)->toBe('hello');
    expect($update->message->chatId)->toBe('123');
    expect($update->message->fromId)->toBe('456');
});

it('parses a callback query', function () {
    $update = $this->parser->parseTelegram([
        'update_id' => 2,
        'callback_query' => [
            'id' => 'cb1',
            'from' => ['id' => 456],
            'message' => ['message_id' => 20, 'chat' => ['id' => 123]],
            'data' => 'action:new_content',
        ],
    ]);

    expect($update->updateType)->toBe('callback_query');
    expect($update->callback->data)->toBe('action:new_content');
    expect($update->callback->chatId)->toBe('123');
});

it('parses a photo message', function () {
    $update = $this->parser->parseTelegram([
        'update_id' => 3,
        'message' => [
            'message_id' => 30,
            'date' => 1700000000,
            'chat' => ['id' => 123, 'type' => 'private'],
            'from' => ['id' => 456],
            'photo' => [
                ['file_id' => 'small'],
                ['file_id' => 'large'],
            ],
            'caption' => 'a photo',
        ],
    ]);

    expect($update->message->messageType)->toBe('photo');
    expect($update->message->mediaFileIds)->toBe(['small', 'large']);
    expect($update->message->caption)->toBe('a photo');
});

it('marks unknown update types', function () {
    $update = $this->parser->parseTelegram(['update_id' => 4]);

    expect($update->updateType)->toBe('unknown');
});
