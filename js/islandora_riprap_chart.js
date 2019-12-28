/*
@file
Javascript that renders a Chart.js line chart.
*/

(function (Drupal, $) {
  "use strict";

  var IslandoraRiprapLineChartCanvas = document.getElementById('islandora-riprap-fail-events-chart');
  var IslandoraRiprapLineChartData = drupalSettings.islandora_riprap.chart_data;

  if (IslandoraRiprapLineChartData == null) {
    // Print a happy message.
    var message = 'No failed fixity check events found. Yay!';
    $('#islandora-riprap-fail-events-no-events').text(message).css({"font-size":"x-large","margin-top":"-5em"});
  } else {
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
