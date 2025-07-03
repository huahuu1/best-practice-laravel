import axios from 'axios';

class KafkaService {
    /**
     * Constructor - Set up Axios with CSRF token
     */
    constructor() {
        // Configure Axios to include CSRF token
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

        // Get the CSRF token from the meta tag
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
        } else {
            console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
        }
    }

    /**
     * Send a message to a Kafka topic
     *
     * @param {string} topic - The Kafka topic
     * @param {object|string} message - The message to send
     * @param {string|null} key - Optional message key
     * @returns {Promise} - Response from the Kafka API
     */
    async sendMessage(topic, message, key = null) {
        try {
            const response = await axios.post('/api/kafka/produce', {
                topic,
                message: typeof message === 'object' ? JSON.stringify(message) : message,
                key
            });
            return response.data;
        } catch (error) {
            console.error('Kafka send error:', error);
            throw error;
        }
    }

    /**
     * Send a QR code scan event
     *
     * @param {number} tableId - The table ID that was scanned
     * @returns {Promise} - Response from the Kafka API
     */
    async sendQRScanEvent(tableId) {
        const eventData = {
            event: 'qr_scan',
            tableId: tableId,
            timestamp: new Date().toISOString()
        };

        return this.sendMessage(
            this.getTopicName('qr_scan_events', 'qr-scan-events'),
            eventData,
            `table-${tableId}`
        );
    }

    /**
     * Get a topic name from config or fallback to default
     *
     * @param {string} configKey - The key in the topics config
     * @param {string} defaultTopic - Default topic name if not found
     * @returns {string} - The topic name
     */
    getTopicName(configKey, defaultTopic) {
        try {
            if (window.kafkaConfig && window.kafkaConfig.topics && window.kafkaConfig.topics[configKey]) {
                return window.kafkaConfig.topics[configKey];
            }
        } catch (e) {
            console.warn('Error accessing Kafka config:', e);
        }
        return defaultTopic;
    }
}

// Create a singleton instance
const kafkaService = new KafkaService();
export default kafkaService;
