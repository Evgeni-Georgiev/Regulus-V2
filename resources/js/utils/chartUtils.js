import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';

// Register ChartJS components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

// Format helpers
const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

/**
 * Formats a date for display on chart X-axis based on the selected time period
 * @param {Date|string} date - The date to format
 * @param {string} period - The time period ('24h', '7d', '30d', '90d', 'All')
 * @returns {string} Formatted date string
 */
export const formatDateForXAxis = (date, period) => {
    const d = new Date(date);

    switch(period) {
        case '24h':
            // Include date + hour: "May 15, 6 AM", "May 15, 2 PM"
            const hour = d.getHours();
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            })}, ${hour12} ${ampm}`;

        case '7d':
            // Include day of week + date: "Mon, May 15"
            return `${d.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            })}`;

        case '30d':
            // Include month + day: "May 15"
            return d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });

        case '90d':
            // Month + year if needed: "Jan 2023", "Mar 2023"
            const monthDay = d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
            return monthDay;

        case 'All':
            // Month + year: "Jan 2022", "Apr 2023"
            return d.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric'
            });

        default:
            return d.toLocaleDateString();
    }
};

/**
 * Generates evenly spaced date ticks for chart X-axis
 * @param {Array} data - Array of data points with recorded_at property
 * @param {string} period - The time period ('24h', '7d', '30d', '90d', 'All')
 * @returns {Array} Array of tick objects with value, label, and date properties
 */
export const generateDateTicks = (data, period) => {
    if (!data.length) return [];

    const sortedDates = [...data].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    const startDate = new Date(sortedDates[0].recorded_at);
    const endDate = new Date(sortedDates[sortedDates.length - 1].recorded_at);
    const range = endDate - startDate;

    // Reduce tick count for periods with longer label text
    let tickCount;
    switch(period) {
        case '24h': tickCount = 6; break;    // 6 ticks (longer text needs more space)
        case '7d': tickCount = 5; break;     // 5 ticks (longer text needs more space)
        case '30d': tickCount = 6; break;    // 6 ticks
        case '90d': tickCount = 5; break;    // 5 ticks
        case 'All': tickCount = 6; break;    // 6 ticks
        default: tickCount = 5;
    }

    // Generate evenly spaced ticks
    const ticks = [];
    for (let i = 0; i < tickCount; i++) {
        const tickPosition = i / (tickCount - 1);
        const tickDate = new Date(startDate.getTime() + (range * tickPosition));
        const formattedTick = formatDateForXAxis(tickDate, period);

        ticks.push({
            value: tickPosition,
            label: formattedTick,
            date: tickDate
        });
    }

    return ticks;
};

/**
 * Samples data points to reduce density for different time periods
 * @param {Array} data - Array of data points
 * @param {string} period - The time period ('24h', '7d', '30d', '90d', 'All')
 * @returns {Array} Sampled data points
 */
export const sampleDataPoints = (data, period) => {
    if (!data.length) return [];

    const sortedData = [...data].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    // Fibonacci-based sampling rates (60, 180, 480, 1260, 3300 minutes)
    const intervals = {
        '24h': 60,     // 1 hour
        '7d': 60 * 3,  // 3 hours
        '30d': 60 * 8, // 8 hours
        '90d': 60 * 21, // 21 hours
        'All': 60 * 55  // ~2.3 days
    };

    const samplingInterval = intervals[period] || 15;

    // If we have few data points, return as is
    if (sortedData.length < 30) return sortedData;

    const result = [];
    let lastTimestamp = null;

    for (const point of sortedData) {
        const currentTime = new Date(point.recorded_at);

        if (!lastTimestamp ||
            (currentTime - lastTimestamp) >= (samplingInterval * 60 * 1000)) {
            result.push(point);
            lastTimestamp = currentTime;
        }
    }

    // Always include the most recent point
    const lastPoint = sortedData[sortedData.length - 1];
    if (result[result.length - 1] !== lastPoint) {
        result.push(lastPoint);
    }

    return result;
};

/**
 * Filters snapshot data based on time period
 * @param {Array} snapshotData - Original snapshot data
 * @param {string} period - The time period ('24h', '7d', '30d', '90d', 'All')
 * @returns {Array} Filtered and sampled data
 */
export const filterSnapshotByPeriod = (snapshotData, period) => {
    if (!snapshotData.length) return [];

    const now = new Date().getTime();

    // Filter out invalid dates
    const validSnapshots = snapshotData.filter(item =>
        !isNaN(new Date(item.recorded_at).getTime())
    );

    const timeRanges = {
        '24h': now - (24 * 60 * 60 * 1000),
        '7d': now - (7 * 24 * 60 * 60 * 1000),
        '30d': now - (30 * 24 * 60 * 60 * 1000),
        '90d': now - (90 * 24 * 60 * 60 * 1000)
    };

    // Apply time filter if not "All"
    let periodFiltered = validSnapshots;
    if (period !== 'All') {
        const cutoff = timeRanges[period];
        periodFiltered = validSnapshots.filter(item =>
            new Date(item.recorded_at).getTime() >= cutoff
        );
    }

    // Apply sampling based on period
    return sampleDataPoints(periodFiltered, period);
};

/**
 * Creates chart data configuration
 * @param {Array} filteredData - Filtered snapshot data
 * @param {boolean} isDarkMode - Whether dark mode is active
 * @param {string} valueKey - The key to use for Y-axis values
 * @param {Object} options - Additional options (colors, etc.)
 * @returns {Object|null} Chart.js data configuration
 */
export const createChartData = (filteredData, isDarkMode, valueKey = 'total_portfolio_value', options = {}) => {
    if (!filteredData.length) return null;

    const sortedData = [...filteredData].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    // For the chart display, use numeric indices for even spacing
    const labels = sortedData.map((_, index) => index);

    // Use different colors based on theme
    const borderColor = isDarkMode ? options.darkBorderColor || '#34d399' : options.lightBorderColor || '#10B981';

    return {
        labels,
        datasets: [{
            label: options.label || 'Value',
            data: sortedData.map(entry => entry[valueKey]),
            borderColor: borderColor,
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);

                if (isDarkMode) {
                    gradient.addColorStop(0, options.darkGradientStart || 'rgba(52, 211, 153, 0.3)');
                    gradient.addColorStop(1, options.darkGradientEnd || 'rgba(52, 211, 153, 0)');
                } else {
                    gradient.addColorStop(0, options.lightGradientStart || 'rgba(16, 185, 129, 0.2)');
                    gradient.addColorStop(1, options.lightGradientEnd || 'rgba(16, 185, 129, 0)');
                }

                return gradient;
            },
            fill: true,
            tension: options.tension || 0.3,
            borderWidth: options.borderWidth || 2.5,
            pointRadius: options.pointRadius || 0,
        }]
    };
};

/**
 * Creates chart options configuration
 * @param {Array} filteredData - Filtered snapshot data
 * @param {string} period - The time period ('24h', '7d', '30d', '90d', 'All')
 * @param {boolean} isDarkMode - Whether dark mode is active
 * @param {Function} formatYValue - Function to format Y-axis values
 * @returns {Object} Chart.js options configuration
 */
export const createChartOptions = (filteredData, period, isDarkMode, formatYValue = null) => {
    if (!filteredData.length) {
        return { responsive: true, maintainAspectRatio: false };
    }

    // Generate appropriate ticks for the time period
    const dateTicks = generateDateTicks(filteredData, period);

    // Dark mode specific styles
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDarkMode ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';

    const tooltipBackgroundColor = isDarkMode
        ? 'rgba(31, 41, 55, 0.95)'
        : 'rgba(255, 255, 255, 0.95)';
    const tooltipTitleColor = isDarkMode ? '#9CA3AF' : '#6B7280';
    const tooltipBodyColor = isDarkMode ? '#F3F4F6' : '#111827';
    const tooltipBorderColor = isDarkMode
        ? 'rgba(75, 85, 99, 0.5)'
        : 'rgba(209, 213, 219, 0.5)';

    // Default Y-axis formatter if none provided
    const defaultYFormatter = (value) => {
        if (value >= 1000) {
            return `$${(value / 1000).toFixed(1)}k`;
        }
        return `$${value.toFixed(0)}`;
    };

    const yValueFormatter = formatYValue || defaultYFormatter;

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: tooltipBackgroundColor,
                titleColor: tooltipTitleColor,
                bodyColor: tooltipBodyColor,
                borderColor: tooltipBorderColor,
                borderWidth: 1,
                callbacks: {
                    title: (tooltipItems) => {
                        const dataIndex = tooltipItems[0].dataIndex;
                        const point = filteredData[dataIndex];
                        if (!point) return '';

                        const date = new Date(point.recorded_at);
                        return date.toLocaleString('en-US', {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    },
                    label: (context) => {
                        return currencyFormatter.format(context.parsed.y);
                    }
                },
                padding: 10,
                displayColors: false,
            }
        },
        scales: {
            x: {
                grid: {
                    display: true,
                    color: gridColor,
                    drawBorder: false,
                    drawTicks: false,
                    tickLength: 10
                },
                ticks: {
                    color: textColor,
                    padding: 10,
                    autoSkip: false,
                    maxRotation: period === '24h' ? 45 : 0,
                    minRotation: 0,
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    callback: (value, index, values) => {
                        // Only show a subset of labels to prevent overlap
                        let interval;
                        switch(period) {
                            case '24h': interval = Math.ceil(values.length / 4); break;
                            case '7d': interval = Math.ceil(values.length / 4); break;
                            case '30d': interval = Math.ceil(values.length / 5); break;
                            case '90d':
                            case 'All': interval = Math.ceil(values.length / 5); break;
                            default: interval = Math.ceil(values.length / 4);
                        }

                        // Only show labels at intervals to prevent crowding
                        if (index % interval !== 0 && index !== values.length - 1) {
                            return '';
                        }

                        // Find the correct tick label based on position
                        const position = value / (filteredData.length - 1);
                        const closestTick = dateTicks.reduce((prev, curr) =>
                            Math.abs(curr.value - position) < Math.abs(prev.value - position) ? curr : prev
                        );
                        return closestTick.label;
                    }
                }
            },
            y: {
                position: 'right',
                beginAtZero: false,
                grid: {
                    color: gridColor,
                    drawBorder: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: 10,
                    callback: yValueFormatter
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        },
        elements: {
            line: {
                borderWidth: 2.5,
                tension: 0.2,
            },
            point: {
                radius: 0,
                hitRadius: 10,
                hoverRadius: 4,
            }
        },
        layout: {
            padding: {
                top: 10,
                right: 20,
                bottom: 30,
                left: 10
            }
        }
    };
}; 