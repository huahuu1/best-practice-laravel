<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Kafka\KafkaProducer;
use App\Services\Kafka\KafkaConsumer;

class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the Kafka producer service
        $this->app->singleton(KafkaProducer::class, function ($app) {
            return new KafkaProducer();
        });

        // Register the Kafka consumer service
        $this->app->singleton(KafkaConsumer::class, function ($app) {
            return new KafkaConsumer();
        });

        // Log to show that Kafka service provider is loaded
        // $this->app->booted(function () {
        //     $this->app['log']->info('Kafka service provider loaded successfully');
        // });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Check if we have RdKafka extension
        if (!extension_loaded('rdkafka')) {
            $this->app['log']->warning('RdKafka PHP extension is not installed. Kafka integration will not work properly.');
        }
    }
}
