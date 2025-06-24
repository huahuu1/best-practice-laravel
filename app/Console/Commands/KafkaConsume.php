<?php

namespace App\Console\Commands;

use App\Services\Kafka\KafkaConsumer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KafkaConsume extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume
                            {--topic= : Kafka topic(s) to consume, comma separated}
                            {--group= : Consumer group ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume messages from Kafka topic';

    /**
     * Execute the console command.
     */
    public function handle(KafkaConsumer $consumer)
    {
        $topics = $this->option('topic') ? explode(',', $this->option('topic')) : null;
        $groupId = $this->option('group') ?: null;

        // Create consumer with provided options
        if ($topics || $groupId) {
            $consumer = new KafkaConsumer($groupId, $topics);
        }

        $this->info('Starting Kafka consumer...');
        $this->info('Topics: ' . implode(', ', $consumer->getTopics()));
        $this->info('Group ID: ' . $consumer->getGroupId());
        $this->info('Press Ctrl+C to stop');

        try {
            $consumer->consume(function($message) {
                $topic = $message['topic'];
                $partition = $message['partition'];
                $offset = $message['offset'];
                $value = $message['value'];

                $this->info("Received message from topic: $topic, partition: $partition, offset: $offset");
                $this->info("Value: $value");

                // Process the message here
                Log::info("Kafka message received", $message);

                return true; // Return true to commit the offset
            });
        } catch (\Exception $e) {
            $this->error('Error consuming Kafka messages: ' . $e->getMessage());
            Log::error('Kafka consumer error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
