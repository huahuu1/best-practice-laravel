import React from 'react';
import QRCodeReact from 'react-qr-code';
import kafkaService from '../services/KafkaService';
import { Card } from 'antd';

// Define base URL for the application using environment variables if available
const BASE_URL = 'http://172.16.0.24:9999';

const QRCode = ({ table, onScan }) => {
    // Generate the URL for this table's menu using the IP address
    const menuUrl = `${BASE_URL}/react/tables/${table.id}/menu`;

    // Function to handle QR code scan simulation (for testing)
    const handleSimulateScan = async () => {
        try {
            // Send the QR scan event to Kafka
            await kafkaService.sendQRScanEvent(table.id);

            // Call the onScan callback if provided
            if (onScan) {
                onScan(table.id);
            }

            // Redirect to the menu page
            window.location.href = menuUrl;
        } catch (error) {
            console.error('Error simulating QR scan:', error);
        }
    };

    return (
        <div className="qr-code-container">
            <div className="qr-code">
                <QRCodeReact
                    value={menuUrl}
                    size={200}
                    level="H"
                />
            </div>
            <div className="mt-3 text-center">
                <p className="mb-2">Scan to access menu for {table.name}</p>
                <p className="mb-2 small text-muted">{menuUrl}</p>
                <button
                    className="btn btn-primary"
                    onClick={handleSimulateScan}
                >
                    Simulate Scan
                </button>
                <button
                    className="btn btn-secondary ms-2"
                    onClick={() => window.open(menuUrl, '_blank')}
                >
                    View Menu
                </button>
            </div>
        </div>
    );
};

export default QRCode;
