<?php

namespace App\Services\Kafka;

class KafkaProducer
{
    /**
     * The broker list for Kafka
     *
     * @var string
     */
    protected $broker;

    /**
     * KafkaProducer constructor.
     */
    public function __construct()
    {
        $this->broker = env('KAFKA_BROKER', 'kafka:29092');
    }

    /**
     * Send message to Kafka topic
     *
     * @param string $topic
     * @param mixed $message
     * @param string|null $key
     * @return void
     * @throws \Exception
     */
    public function send(string $topic, $message, string $key = null): void
    {
        // Prepare config
        $config = new \RdKafka\Conf();
        $config->set('metadata.broker.list', $this->broker);

        // Create producer
        $producer = new \RdKafka\Producer($config);

        // Get topic
        $kafkaTopic = $producer->newTopic($topic);

        // Ensure message is a string
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }

        // RD_KAFKA_PARTITION_UA lets librdkafka choose the partition
        // The last parameter is for message flags (0 for none)
        $kafkaTopic->produce(\RD_KAFKA_PARTITION_UA, 0, $message, $key);

        // Make sure all messages are sent
        $producer->flush(10000); // 10 seconds timeout
    }
}
