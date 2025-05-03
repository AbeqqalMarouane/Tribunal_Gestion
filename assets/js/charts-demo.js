document.addEventListener('DOMContentLoaded', function() {
    fetch('../include/getStatistics.php')
        .then(response => response.json())
        .then(data => {
            console.log(data); // Add this line
            const congePerMonthCurrentYear = data.congePerMonthCurrentYear;
            const congePerMonthPreviousYear = data.congePerMonthPreviousYear;
            const employeeCongeStats = data.employeeCongeStats;
            const congeLeftStats = data.congeLeftStats;

            console.log(congePerMonthCurrentYear); // Add this line
            console.log(congePerMonthPreviousYear); // Add this line
            console.log(employeeCongeStats); // Add this line
            console.log(congeLeftStats); // Add this line

            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const congePerMonthDataCurrentYear = Array(12).fill(0);
            const congePerMonthDataPreviousYear = Array(12).fill(0);

            congePerMonthCurrentYear.forEach(item => {
                congePerMonthDataCurrentYear[item.month - 1] = item.count;
            });

            congePerMonthPreviousYear.forEach(item => {
                congePerMonthDataPreviousYear[item.month - 1] = item.count;
            });

            var congePerMonthCtx = document.getElementById('congePerMonthChart').getContext('2d');
            var congePerMonthConfig = {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'عدد الرخص',
                        data: congePerMonthDataCurrentYear,
                        backgroundColor: window.chartColors.green,
                        borderColor: window.chartColors.green,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            new Chart(congePerMonthCtx, congePerMonthConfig);

            var employeeCongeCtx = document.getElementById('employeeCongeChart').getContext('2d');
            var employeeCongeConfig = {
                type: 'pie',
                data: {
                    labels: ['مع رخص', 'بدون رخص'],
                    datasets: [{
                        label: 'الموظفين',
                        data: [employeeCongeStats.withConge, employeeCongeStats.withoutConge],
                        backgroundColor: [
                            window.chartColors.blue,
                            window.chartColors.gray
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'نسبة الموظفين الذين أخذوا رخص'
                        }
                    }
                }
            };
            new Chart(employeeCongeCtx, employeeCongeConfig);

            var congeLeftCtx = document.getElementById('congeLeftChart').getContext('2d');
            var congeLeftConfig = {
                type: 'doughnut',
                data: {
                    labels: ['باقي الرخص صفر', 'باقي الرخص'],
                    datasets: [{
                        label: 'باقي الرخص',
                        data: [congeLeftStats.withNoCongeLeft, congeLeftStats.withCongeLeft],
                        backgroundColor: [
                            window.chartColors.green,
                            window.chartColors.blue
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'باقي الرخص للموظفين'
                        }
                    }
                }
            };
            new Chart(congeLeftCtx, congeLeftConfig);

            var lineChartCtx = document.getElementById('congeLineChart').getContext('2d');
            var lineChartConfig = {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'السنة الحالية',
                        fill: false,
                        backgroundColor: window.chartColors.green,
                        borderColor: window.chartColors.green,
                        data: congePerMonthDataCurrentYear,
                    }, {
                        label: 'السنة السابقة',
                        borderDash: [3, 5],
                        backgroundColor: window.chartColors.gray,
                        borderColor: window.chartColors.gray,
                        data: congePerMonthDataPreviousYear,
                        fill: false,
                    }]
                },
                options: {
                    responsive: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            align: 'end',
                        },
                        title: {
                            display: true,
                            text: 'عدد الرخص لكل شهر (السنة الحالية والسنة السابقة)',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            titleMarginBottom: 10,
                            bodySpacing: 10,
                            xPadding: 16,
                            yPadding: 16,
                            borderColor: window.chartColors.border,
                            borderWidth: 1,
                            backgroundColor: '#fff',
                            bodyFontColor: window.chartColors.text,
                            titleFontColor: window.chartColors.text,
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.raw;
                                }
                            },
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                drawBorder: false,
                                color: window.chartColors.border,
                            },
                            title: {
                                display: false,
                            }
                        },
                        y: {
                            display: true,
                            grid: {
                                drawBorder: false,
                                color: window.chartColors.border,
                            },
                            title: {
                                display: false,
                            },
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    return value;
                                }
                            },
                        }
                    }
                }
            };
            new Chart(lineChartCtx, lineChartConfig);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
});
