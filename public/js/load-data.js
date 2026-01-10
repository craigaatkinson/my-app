// Function to fetch and store load data
async function fetchAndStoreLoadData() {
    try {
        const response = await fetch('/api/load-data');
        const result = await response.json();
        
        if (result.success) {
            // Store the data in localStorage
            localStorage.setItem('loadData', JSON.stringify(result.data));
            console.log('Load data successfully stored in localStorage');
            return result.data;
        } else {
            console.error('Failed to fetch load data:', result.error);
            return null;
        }
    } catch (error) {
        console.error('Error fetching load data:', error);
        return null;
    }
}

// Function to get load data from localStorage
function getStoredLoadData() {
    const storedData = localStorage.getItem('loadData');
    return storedData ? JSON.parse(storedData) : null;
}

// Function to check if stored data is older than 1 hour
function isStoredDataStale() {
    const lastUpdated = localStorage.getItem('loadDataLastUpdated');
    if (!lastUpdated) return true;
    
    const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds
    return (Date.now() - parseInt(lastUpdated)) > oneHour;
}

// Function to update stored data timestamp
function updateStoredDataTimestamp() {
    localStorage.setItem('loadDataLastUpdated', Date.now().toString());
}

// Main function to get load data (fetches new data if needed)
async function getLoadData() {
    // Check if we have stored data and if it's not stale
    const storedData = getStoredLoadData();
    if (storedData && !isStoredDataStale()) {
        return storedData;
    }
    
    // Fetch new data if we don't have stored data or if it's stale
    const newData = await fetchAndStoreLoadData();
    if (newData) {
        updateStoredDataTimestamp();
    }
    return newData;
}

// Export functions for use in other files
window.loadDataManager = {
    getLoadData,
    getStoredLoadData,
    fetchAndStoreLoadData
};

// Get load data (will fetch from API if needed)
async function displayLoadData() {
    const data = await window.loadDataManager.getLoadData();
    if (data) {
        // Use the data here
        console.log(data);
    }
}

// Or if you just want to get the stored data without fetching
function getStoredData() {
    const data = window.loadDataManager.getStoredLoadData();
    if (data) {
        // Use the stored data
        console.log(data);
    }
}

// You can access the stored data like this at any time
// const data = JSON.parse(localStorage.getItem('loadData')); 
// console.log(data);

// Call the displayLoadData function to fetch and display the data
