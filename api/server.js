import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import fetch from 'node-fetch';

// Load environment variables
dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;

// Configure CORS
const allowedOrigins = process.env.ALLOWED_ORIGINS?.split(',') || ['http://localhost'];
app.use(cors({
  origin: (origin, callback) => {
    // Allow requests with no origin (like mobile apps or curl)
    if (!origin) return callback(null, true);
    
    if (allowedOrigins.some(allowed => origin.startsWith(allowed.trim()))) {
      callback(null, true);
    } else {
      callback(new Error('Not allowed by CORS'));
    }
  }
}));

app.use(express.json());

// Cache for access token
let tokenCache = {
  token: null,
  expiresAt: 0
};

/**
 * Get Feishu tenant access token
 * @returns {Promise<string>} Access token
 */
async function getAccessToken() {
  // Return cached token if still valid
  if (tokenCache.token && Date.now() < tokenCache.expiresAt) {
    return tokenCache.token;
  }

  try {
    const response = await fetch('https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        app_id: process.env.FEISHU_APP_ID,
        app_secret: process.env.FEISHU_APP_SECRET
      })
    });

    const data = await response.json();
    
    if (data.code !== 0) {
      throw new Error(`Feishu API error: ${data.msg}`);
    }

    // Cache token (expires in 2 hours, we refresh 5 minutes early)
    tokenCache.token = data.tenant_access_token;
    tokenCache.expiresAt = Date.now() + (115 * 60 * 1000); // 1h 55min

    return tokenCache.token;
  } catch (error) {
    console.error('Failed to get access token:', error);
    throw error;
  }
}

/**
 * Fetch records from Feishu multi-dimensional table
 * @returns {Promise<Array>} Array of records
 */
async function fetchFeishuTableRecords() {
  try {
    const token = await getAccessToken();
    const appToken = process.env.FEISHU_APP_TOKEN;
    const tableId = process.env.FEISHU_TABLE_ID;

    if (!appToken || !tableId) {
      throw new Error('FEISHU_APP_TOKEN or FEISHU_TABLE_ID not configured');
    }

    // Fetch all records with pagination
    let allRecords = [];
    let pageToken = null;
    let hasMore = true;

    while (hasMore) {
      const url = new URL(`https://open.feishu.cn/open-apis/bitable/v1/apps/${appToken}/tables/${tableId}/records`);
      url.searchParams.append('page_size', '100');
      if (pageToken) {
        url.searchParams.append('page_token', pageToken);
      }

      const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();

      if (data.code !== 0) {
        throw new Error(`Feishu API error: ${data.msg}`);
      }

      if (data.data && data.data.items) {
        allRecords = allRecords.concat(data.data.items);
      }

      hasMore = data.data.has_more;
      pageToken = data.data.page_token;
    }

    return allRecords;
  } catch (error) {
    console.error('Failed to fetch Feishu table records:', error);
    throw error;
  }
}

/**
 * Process Feishu record to ad format
 * @param {Object} record - Feishu table record
 * @returns {Object} Processed ad object
 */
function processRecord(record) {
  const fields = record.fields;
  
  return {
    id: fields.id || fields.ad_id || record.record_id,
    title: fields.title || fields.ad_title || '',
    url: fields.url || fields.ad_url || fields.link || '',
    content: fields.content || fields.ad_content || fields.description || '',
    imageUrl: fields.imageUrl || fields.image_url || fields.ad_image || '',
    tag: fields.tag || fields.ad_tag || fields.label || '',
    type: fields.type || 'card', // Default to card type
    priority: fields.priority || 'medium',
    startDate: fields.startDate || fields.start_date || fields.start_time || null,
    endDate: fields.endDate || fields.end_date || fields.end_time || null,
    enabled: fields.enabled !== false // Default to true if not specified
  };
}

/**
 * Filter and validate ads
 * @param {Array} ads - Array of ad objects
 * @returns {Array} Filtered ads
 */
function filterAds(ads) {
  const now = new Date();
  
  return ads.filter(ad => {
    // Must be enabled
    if (!ad.enabled) return false;
    
    // Must have required fields
    if (!ad.id || !ad.url) return false;
    
    // Check date range
    if (ad.startDate) {
      const startDate = new Date(ad.startDate);
      if (now < startDate) return false;
    }
    
    if (ad.endDate) {
      const endDate = new Date(ad.endDate);
      if (now > endDate) return false;
    }
    
    return true;
  });
}

/**
 * Sort ads by priority
 * @param {Array} ads - Array of ad objects
 * @returns {Array} Sorted ads
 */
function sortAdsByPriority(ads) {
  const priorityOrder = { high: 3, medium: 2, low: 1 };
  
  return ads.sort((a, b) => {
    const aPriority = priorityOrder[a.priority] || 0;
    const bPriority = priorityOrder[b.priority] || 0;
    return bPriority - aPriority;
  });
}

// API Endpoints

/**
 * GET /api/ads - Fetch all active advertisements
 */
app.get('/api/ads', async (req, res) => {
  try {
    // Check if Feishu is configured
    if (!process.env.FEISHU_APP_ID || !process.env.FEISHU_APP_SECRET) {
      return res.status(500).json({
        error: 'Feishu API not configured',
        message: 'Please set FEISHU_APP_ID and FEISHU_APP_SECRET environment variables'
      });
    }

    // Fetch records from Feishu
    const records = await fetchFeishuTableRecords();
    
    // Process records
    const ads = records.map(processRecord);
    
    // Filter and sort
    const activeAds = filterAds(ads);
    const sortedAds = sortAdsByPriority(activeAds);
    
    res.json({
      success: true,
      count: sortedAds.length,
      data: sortedAds
    });
  } catch (error) {
    console.error('Error fetching ads:', error);
    res.status(500).json({
      error: 'Failed to fetch advertisements',
      message: error.message
    });
  }
});

/**
 * GET /api/ads/:id - Fetch specific advertisement by ID
 */
app.get('/api/ads/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    const records = await fetchFeishuTableRecords();
    const ads = records.map(processRecord);
    
    const ad = ads.find(a => a.id === id);
    
    if (!ad) {
      return res.status(404).json({
        error: 'Advertisement not found',
        message: `No advertisement found with ID: ${id}`
      });
    }
    
    res.json({
      success: true,
      data: ad
    });
  } catch (error) {
    console.error('Error fetching ad:', error);
    res.status(500).json({
      error: 'Failed to fetch advertisement',
      message: error.message
    });
  }
});

/**
 * GET /health - Health check endpoint
 */
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    feishuConfigured: !!(process.env.FEISHU_APP_ID && process.env.FEISHU_APP_SECRET)
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({
    error: 'Internal server error',
    message: err.message
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 Advertisement API server running on port ${PORT}`);
  console.log(`📊 Feishu integration: ${process.env.FEISHU_APP_ID ? '✓ Configured' : '✗ Not configured'}`);
  console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
});
