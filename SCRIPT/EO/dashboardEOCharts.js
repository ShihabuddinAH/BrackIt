// EO Dashboard Charts JavaScript
// Chart initialization and management

class DashboardCharts {
  constructor() {
    this.charts = {};
    this.commonOptions = {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: {
          top: 10,
          bottom: 10,
          left: 10,
          right: 10,
        },
      },
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            usePointStyle: true,
            padding: 20,
            font: {
              size: 12,
              weight: "500",
            },
            color: "#ffffff",
            boxWidth: 12,
            boxHeight: 12,
          },
        },
        tooltip: {
          backgroundColor: "rgba(0, 0, 0, 0.9)",
          titleColor: "#ffffff",
          bodyColor: "#ffffff",
          borderColor: "#4f46e5",
          borderWidth: 1,
          cornerRadius: 8,
          padding: 12,
          titleFont: {
            size: 13,
            weight: "bold",
          },
          bodyFont: {
            size: 12,
          },
        },
      },
      animation: {
        duration: 800,
        easing: "easeInOutQuart",
      },
    };
  }

  showLoading() {
    document.querySelectorAll(".chart-content").forEach((content) => {
      content.classList.add("loading");
    });
  }

  hideLoading() {
    setTimeout(() => {
      document.querySelectorAll(".chart-content").forEach((content) => {
        content.classList.remove("loading");
      });
    }, 500);
  }

  initRevenueChart(labels, data) {
    const ctx = document.getElementById("revenueChart");
    if (!ctx) return;

    this.charts.revenue = new Chart(ctx.getContext("2d"), {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Pendapatan (Rp)",
            data: data,
            borderColor: "#4f46e5",
            backgroundColor: "rgba(79, 70, 229, 0.1)",
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#4f46e5",
            pointBorderColor: "#ffffff",
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
          },
        ],
      },
      options: {
        ...this.commonOptions,
        plugins: {
          ...this.commonOptions.plugins,
          legend: {
            display: false,
          },
          tooltip: {
            ...this.commonOptions.plugins.tooltip,
            callbacks: {
              label: function (context) {
                const value = context.parsed.y;
                if (value >= 1000000) {
                  return "Pendapatan: Rp " + (value / 1000000).toFixed(1) + "M";
                } else if (value >= 1000) {
                  return "Pendapatan: Rp " + (value / 1000).toFixed(1) + "K";
                } else {
                  return "Pendapatan: Rp " + value.toLocaleString();
                }
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(255, 255, 255, 0.1)",
            },
            ticks: {
              color: "#ffffff",
              callback: function (value) {
                if (value >= 1000000) {
                  return "Rp " + (value / 1000000).toFixed(1) + "M";
                } else if (value >= 1000) {
                  return "Rp " + (value / 1000).toFixed(1) + "K";
                } else {
                  return "Rp " + value.toLocaleString();
                }
              },
            },
          },
          x: {
            grid: {
              color: "rgba(255, 255, 255, 0.1)",
            },
            ticks: {
              color: "#ffffff",
            },
          },
        },
      },
    });
  }

  initParticipationChart(labels, data) {
    const ctx = document.getElementById("participationChart");
    if (!ctx) return;

    this.charts.participation = new Chart(ctx.getContext("2d"), {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Jumlah Peserta",
            data: data,
            borderColor: "#10b981",
            backgroundColor: "rgba(16, 185, 129, 0.2)",
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#10b981",
            pointBorderColor: "#ffffff",
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
          },
        ],
      },
      options: {
        ...this.commonOptions,
        plugins: {
          ...this.commonOptions.plugins,
          legend: {
            display: false,
          },
          tooltip: {
            ...this.commonOptions.plugins.tooltip,
            callbacks: {
              label: function (context) {
                return "Peserta: " + context.parsed.y + " orang";
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "rgba(255, 255, 255, 0.1)",
            },
            ticks: {
              color: "#ffffff",
              callback: function (value) {
                return value + " orang";
              },
            },
          },
          x: {
            grid: {
              color: "rgba(255, 255, 255, 0.1)",
            },
            ticks: {
              color: "#ffffff",
            },
          },
        },
      },
    });
  }

  initStatusChart(labels, data, colors) {
    const ctx = document.getElementById("statusChart");
    if (!ctx) return;

    this.charts.status = new Chart(ctx.getContext("2d"), {
      type: "doughnut",
      data: {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: colors,
            borderWidth: 3,
            borderColor: "#2a2a2a",
            hoverBorderWidth: 4,
            hoverBorderColor: "#ffffff",
            cutout: "65%",
          },
        ],
      },
      options: {
        ...this.commonOptions,
        plugins: {
          ...this.commonOptions.plugins,
          legend: {
            position: "bottom",
            labels: {
              padding: 15,
              usePointStyle: true,
              pointStyle: "circle",
              color: "#ffffff",
              font: {
                size: 11,
              },
            },
          },
        },
      },
    });
  }

  initFormatChart(labels, data) {
    const ctx = document.getElementById("formatChart");
    if (!ctx) return;

    const colors = ["#f59e0b", "#8b5cf6", "#ef4444", "#06b6d4", "#84cc16"];

    this.charts.format = new Chart(ctx.getContext("2d"), {
      type: "pie",
      data: {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: colors.slice(0, data.length),
            borderWidth: 3,
            borderColor: "#2a2a2a",
            hoverBorderWidth: 4,
            hoverBorderColor: "#ffffff",
          },
        ],
      },
      options: {
        ...this.commonOptions,
        plugins: {
          ...this.commonOptions.plugins,
          legend: {
            position: "bottom",
            labels: {
              padding: 15,
              usePointStyle: true,
              pointStyle: "circle",
              color: "#ffffff",
              font: {
                size: 11,
              },
            },
          },
        },
      },
    });
  }

  initTopTournamentsChart(names, participants) {
    const ctx = document.getElementById("topTournamentsChart");
    if (!ctx) return;

    this.charts.topTournaments = new Chart(ctx.getContext("2d"), {
      type: "bar",
      data: {
        labels: names,
        datasets: [
          {
            label: "Jumlah Peserta",
            data: participants,
            backgroundColor: "rgba(139, 92, 246, 0.8)",
            borderColor: "#8b5cf6",
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: false,
          },
        ],
      },
      options: {
        ...this.commonOptions,
        indexAxis: "y",
        plugins: {
          ...this.commonOptions.plugins,
          legend: {
            display: false,
          },
          tooltip: {
            ...this.commonOptions.plugins.tooltip,
            callbacks: {
              label: function (context) {
                return "Peserta: " + context.parsed.x + " orang";
              },
            },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            grid: {
              color: "rgba(255, 255, 255, 0.1)",
            },
            ticks: {
              color: "#ffffff",
              callback: function (value) {
                return value + " peserta";
              },
            },
          },
          y: {
            grid: {
              display: false,
            },
            ticks: {
              color: "#ffffff",
              callback: function (value, index) {
                const label = this.getLabelForValue(value);
                return label.length > 20
                  ? label.substring(0, 20) + "..."
                  : label;
              },
            },
          },
        },
      },
    });
  }

  initializeAll(chartData) {
    try {
      this.showLoading();

      // Initialize all charts
      this.initRevenueChart(chartData.revenueLabels, chartData.revenueData);
      this.initParticipationChart(
        chartData.participationLabels,
        chartData.participationData
      );
      this.initStatusChart(
        chartData.statusLabels,
        chartData.statusData,
        chartData.statusColors
      );
      this.initFormatChart(chartData.formatLabels, chartData.formatData);
      this.initTopTournamentsChart(
        chartData.topTournamentNames,
        chartData.topTournamentParticipants
      );

      this.hideLoading();
    } catch (error) {
      console.error("Error initializing charts:", error);
      this.showError();
    }
  }

  showError() {
    document.querySelectorAll(".chart-content").forEach((content) => {
      content.innerHTML =
        '<div class="chart-error">Error loading chart data</div>';
    });
  }

  updateChart(chartName, labels, data) {
    if (this.charts[chartName]) {
      this.charts[chartName].data.labels = labels;
      this.charts[chartName].data.datasets[0].data = data;
      this.charts[chartName].update();
    }
  }

  destroyAll() {
    Object.values(this.charts).forEach((chart) => {
      if (chart) chart.destroy();
    });
    this.charts = {};
  }
}

// Tournament action functions
function editTournament(id) {
  alert("Edit turnamen ID: " + id);
  // TODO: Implement edit functionality
}

function viewTournament(id) {
  alert("View turnamen ID: " + id);
  // TODO: Implement view functionality
}

function deleteTournament(id) {
  if (confirm("Apakah Anda yakin ingin menghapus turnamen ini?")) {
    // TODO: Implement delete functionality
    alert("Delete turnamen ID: " + id);
  }
}

// Global dashboard charts instance
window.dashboardCharts = new DashboardCharts();
