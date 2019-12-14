/*
@file
Javascript that renders a Chart.js line chart.
*/

(function (Drupal, $) {
  "use strict";

  var IslandoraRiprapLineChartCanvas = document.getElementById('islandora-riprap-fail-events-chart');
  var IslandoraRiprapLineChartData = drupalSettings.islandora_riprap.chart_data;

  var IslandoraRiprapLineChart = new Chart(IslandoraRiprapLineChartCanvas, {
    type: 'line',
    data: IslandoraRiprapLineChartData,
    options: {
        scales: {
            xAxes: [{
                type: 'time',
                time: {
                    unit: 'month'
                },
		distribution: 'linear'
            }]
        }
    }
  });


}) (Drupal, jQuery);
