<?php

namespace App\Services\Kafka;

class KafkaConsumer
{
    /**
     * The broker list for Kafka
     *
     * @var string
     */
    protected $broker;

    /**
     * @var string
     */
    protected $groupId;

    /**
     * @var array
     */
    protected $topics;

    /**
     * KafkaConsumer constructor.
     *
     * @param string|null $groupId
     * @param array|null $topics
     */
    public function __construct(string $groupId = null, array $topics = null)
    {
        $this->broker = env('KAFKA_BROKER', 'kafka:29092');
        $this->groupId = $groupId ?? env('KAFKA_GROUP_ID', 'laravel-consumer-group');
        $this->topics = $topics ?? [env('KAFKA_TOPIC', 'laravel-topic')];
    }

    /**
     * Get the consumer group ID
     *
     * @return string
     */
    public function getGroupId(): string
    {
        return $this->groupId;
    }

    /**
     * Get the topics being consumed
     *
     * @return array
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    /**
     * Start consuming messages
     *
     * @param callable $callback
     * @return void
     * @throws \Exception
     */
    public function consume(callable $callback): void
    {
        // Configure consumer
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->broker);
        $conf->set('group.id', $this->groupId);
        $conf->set('auto.offset.reset', 'earliest'); // Start reading from the beginning

        // Subscribe to topic(s)
        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe($this->topics);

        // Consume messages
        while (true) {
            $message = $consumer->consume(120 * 1000); // 120 seconds timeout

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $result = call_user_func($callback, [
                        'topic' => $message->topic_name,
                        'partition' => $message->partition,
                        'offset' => $message->offset,
                        'timestamp' => $message->timestamp,
                        'key' => $message->key,
                        'value' => $message->payload,
                    ]);

                    // If callback returns true, commit the message
                    if ($result) {
                        $consumer->commit($message);
                    }
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // End of partition - not an error
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    // Timeout - not an error
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
            }
        }
    }
}
