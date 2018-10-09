'use strict';
var apiToken = $('#apiTokenData').data('token');

function queryPlex (query) {
  return $.ajax({
    type: 'POST',
    url: 'api.php',
    cache: false,
    data: {'postData': query, apiToken: apiToken},
    dataType: 'json'
  });
}

function resetStatCard (divId, tooltipClass) {
  $(divId).empty();
  if (tooltipClass) { $(tooltipClass).tooltip('dispose'); }
}

function precisionRound (number, precision) {
  let factor = Math.pow(10, precision);
  return Math.round(number * factor) / factor;
}

function humanFileSize (bytes, si) {
  let thresh = si ? 1000 : 1024;
  if (Math.abs(bytes) < thresh) {
    return bytes + 'B';
  }
  let units = si ? [' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'] : [' KiB', ' MiB', ' GiB', ' TiB', ' PiB', ' EiB', ' ZiB', ' YiB'];
  let u = -1;
  do {
    bytes /= thresh;
    ++u;
  } while (Math.abs(bytes) >= thresh && u < units.length - 1);
  return bytes.toFixed(1) + units[u];
}