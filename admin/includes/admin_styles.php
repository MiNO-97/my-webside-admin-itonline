<style>
    body {
        font-family: 'Noto Sans Lao', sans-serif;
        background-color: #f8f9fa;
    }
    
    .sidebar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 12px 20px;
        border-radius: 8px;
        margin: 2px 0;
        transition: all 0.3s ease;
    }
    
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }
    
    .main-content {
        padding: 20px;
    }
    
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .btn-primary {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border: none;
        border-radius: 8px;
    }
    
    .table th {
        background-color: #f8f9fa;
        border-top: none;
        font-weight: 600;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-processing {
        background-color: #cce5ff;
        color: #004085;
    }
    
    .status-shipped {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-delivered {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-cancelled {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .status-ordered {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-received {
        background-color: #d4edda;
        color: #155724;
    }
    
    .stock-low {
        color: #dc3545;
        font-weight: bold;
    }
    
    .stock-ok {
        color: #28a745;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .order-number {
        font-family: monospace;
        font-weight: bold;
        color: #667eea;
    }
    
    /* Dashboard specific styles */
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #667eea, #764ba2);
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #11998e, #38ef7d);
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #f093fb, #f5576c);
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #4facfe, #00f2fe);
    }
</style> 