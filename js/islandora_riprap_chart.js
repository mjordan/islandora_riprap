/*
@file
Javascript that renders a Chart.js line chart.
*/

(function (Drupal, $) {
  "use strict";

  // If we're here, there are failed events.
  $('#islandora-riprap-fail-events-no-events').css({"visibility":"hidden"});

  var IslandoraRiprapLineChartCanvas = document.getElementById('islandora-riprap-fail-events-chart');
  var IslandoraRiprapLineChartData = drupalSettings.islandora_riprap.chart_data;

  if (IslandoraRiprapLineChartData != null) {
    var IslandoraRiprapLineChart = new Chart(IslandoraRiprapLineChartCanvas, {
      type: 'scatter',
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
  }


}) (Drupal, jQuery);
