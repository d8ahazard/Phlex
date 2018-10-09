'use strict';
apiToken = $('#apiTokenData').data('token');

/**
 * Checks Plex directly to see if it's online
 */
function getServerStatus () {

  const serverStatusDiv = $('#serverStatus');

  serverStatusDiv.html('Server Status: Loading...');

  const getServerStatus = queryPlex('/');

  getServerStatus.done(function (data) {
    if (data) {
      serverStatusDiv.removeClass('list-group-item-info');
      serverStatusDiv.addClass('list-group-item-success');
      serverStatusDiv.html('Server Status: Online <i class="fas fa-fw fa-check-circle" data-fa-transform="grow-4"></i>');
    } else {
      serverStatusDiv.removeClass('list-group-item-info');
      serverStatusDiv.addClass('list-group-item-danger');
      serverStatusDiv.html(
        'Server Status: Offline ' +
        '<span class="d-flex align-items-center"><i class="fas fa-fw fa-exclamation-circle" data-fa-transform="grow-4"></i></span>');
    }
  });

  getServerStatus.fail(function () {
    serverStatusDiv.removeClass('list-group-item-info');
    serverStatusDiv.addClass('list-group-item-danger');
    serverStatusDiv.html(
      'Server Status: Offline ' +
      '<span class="d-flex align-items-center"><i class="fas fa-fw fa-exclamation-circle" data-fa-transform="grow-4"></i></span>');
  });
}

/**
 * Uses Plex direct to grab current sessions
 *
 * This is the default function because I simply prefer it
 * Additionally, it should be noted that I've decided to query Plex's preferences to grab the maximum upload rate (Internet upload speed)
 * This is most certainly an unneeded API call but I like it, so it's here
 */
function getCurrentActivityViaPlex () {
  $('#loadbar').show();
  $('#currentActivityStreamCount').html('Loading...');
  $('#currentActivity').toggleClass('list-group-item-danger', false).toggleClass('bg-dark', true);
  $('#getCurrentActivity').children('svg').toggleClass('fa-spin', true);

  const getPlexPrefs = queryPlex('/:/prefs');
  const getCurrentActivity = queryPlex('/status/sessions');

  $.when(getPlexPrefs, getCurrentActivity).done(function (prefs, data) {

    let wan_total_max_upload_rate = null;

    $.each(prefs[0].MediaContainer.Setting, function (i, setting) {

      // "Internet Upload Speed" set under Settings->Server->Remote Access
      if (setting.id === 'WanTotalMaxUploadRate') {
        if (setting.value > 0) {
          wan_total_max_upload_rate = setting.value;
        }
      }

    });

    setTimeout(function () { // setTimeout used to make it seem like it's actually refreshing

      if (data[0]) {

        let streams = data[0].MediaContainer.Metadata;
        let stream_count = data[0].MediaContainer.size;
        let wan_bandwidth = 0;

        $.each(streams, function (i, item) {
          if (item.Session.location === 'wan') {
            wan_bandwidth += item.Session.bandwidth;
          }
        });

        let bandwidth = wan_bandwidth > 1000 ? precisionRound((wan_bandwidth / 1000), 1) + ' Mbps' : wan_bandwidth + ' kbps';
        let max_bandwidth = null;
        if (wan_total_max_upload_rate) { max_bandwidth = precisionRound((wan_total_max_upload_rate / 1000), 1) + ' Mbps'; }

        if (stream_count === 0) {
          $('#currentActivityStreamCount').html('No streams');
          $('#getCurrentActivity').children('svg').toggleClass('fa-spin', false);
        } else {
          $('#currentActivityStreamCount').html(
            stream_count + (stream_count === 1 ? ' Stream' : ' Streams') +
            ' <span id="currentActivityBandwidth" title="' + bandwidth + (max_bandwidth ? ' / ' + max_bandwidth : '') +
            '" data-toggle="tooltip"><i class="fas fa-fw fa-info-circle"></i></span>'
          );
          $('#currentActivityBandwidth').tooltip();
          $('#getCurrentActivity').children('svg').toggleClass('fa-spin', false);
        }

      } else {

        $('#currentActivity').toggleClass('list-group-item-danger', true).toggleClass('bg-dark', false);
        $('#currentActivityStreamCount').html('Error');
        $('#getCurrentActivity').children('svg').toggleClass('fa-spin', false);

      }

        $('#loadbar').hide();

    }, 1000);

  });

  $.when(getPlexPrefs, getCurrentActivity).fail(function () {

    setTimeout(function () { // setTimeout used to make it seem like it's actually refreshing
        $('#loadbar').hide();
      $('#currentActivity').toggleClass('list-group-item-danger', true).toggleClass('bg-dark', false);
      $('#currentActivityStreamCount').html('Error!');
      $('#getCurrentActivity').children('svg').toggleClass('fa-spin', false);
    }, 1000);

  });
}

/**
 * Appends library media counts and number of users "Last Seen" within the last 30 days to the stats list underneath Current Activity
 *
 * @param array    includeLibraryTypes        An array of section types to include in the stats list
 * @param array    excludeLibraryIds          An array of section IDs to exclude from the stats list
 * @param boolean  includeSeasonAlbumCounts   Show or hide TV Show Season counts and Artist Album counts
 */
function getLibraryStats (includeLibraryTypes = ['movie', 'show'], excludeLibraryIds = [], includeSeasonAlbumCounts = false) {

  let item_count = 1;

  if ((includeLibraryTypes.indexOf('movie')) > -1) item_count += 1;
  if (((includeLibraryTypes.indexOf('show')) > -1) && !includeSeasonAlbumCounts) item_count += 2;
  if (((includeLibraryTypes.indexOf('show')) > -1) && includeSeasonAlbumCounts) item_count += 3;
  if (((includeLibraryTypes.indexOf('artist')) > -1) && !includeSeasonAlbumCounts) item_count += 2;
  if (((includeLibraryTypes.indexOf('artist')) > -1) && includeSeasonAlbumCounts) item_count += 3;

  let loadingListItem = '<li class="list-group-item bg-dark loading"><span>&nbsp;</span></li>';

  $('#serverInformation').append(loadingListItem.repeat(item_count));

  const getLibraries = queryTautulli({'cmd': 'get_libraries'});
  const getUsers = queryTautulli({'cmd': 'get_users_table', 'length': '50'});

  $.when(getLibraries, getUsers).done(function (allLibraries, allUsers) {

    let statObj = {
      'Movies': {'count': null},
      'TV Shows': {'count': null},
      'TV Seasons': {'count': null},
      'TV Episodes': {'count': null},
      'Artists': {'count': null},
      'Albums': {'count': null},
      'Tracks': {'count': null}
    };

    $.each(allLibraries[0].response.data, function (i, library) {

      if (library.section_type === 'movie' && ((includeLibraryTypes.indexOf('movie')) > -1) && (excludeLibraryIds.indexOf(library.section_id) === -1)) {

        statObj['Movies'].count += Number(library.count);

      } else if (library.section_type === 'show' && ((includeLibraryTypes.indexOf('show')) > -1) && (excludeLibraryIds.indexOf(library.section_id) === -1)) {

        statObj['TV Shows'].count += Number(library.count);
        if (includeSeasonAlbumCounts) statObj['TV Seasons'].count += Number(library.parent_count);
        statObj['TV Episodes'].count += Number(library.child_count);

      } else if (library.section_type === 'artist' && ((includeLibraryTypes.indexOf('artist')) > -1) && (excludeLibraryIds.indexOf(library.section_id) === -1)) {

        statObj['Artists'].count += Number(library.count);
        if (includeSeasonAlbumCounts) statObj['Albums'].count += Number(library.parent_count);
        statObj['Tracks'].count += Number(library.child_count);

      }

    });

    $('#serverInformation .loading').remove();

    $.each(statObj, function (key, val) {
      if (val.count) {
        $('#serverInformation').append(
          '<li class="list-group-item d-flex justify-content-between bg-dark">' +
            '<span>' + key + '</span>' +
            '<span class="text-right">' + val.count.toLocaleString() + '</span>' +
          '</li>'
        );
      }
    });

    let users_active = 0;
    let users_total = allUsers[0].response.data.recordsFiltered;
    let users = allUsers[0].response.data.data;
    let today = Math.round((new Date()).getTime() / 1000);

    $.each(users, function (i, item) {
      let last_seen = item.last_seen;
      if (today - last_seen <= 2592000) {
        users_active++;
      }
    });

    $('#serverInformation').append(
      '<li class="list-group-item d-flex justify-content-between bg-dark">' +
        '<span>Monthly Active Users</span>' +
        '<span id="montlyActiveUsers" class="text-right">' +
          users_active + '<span class="text-muted"> / ' + users_total + '</span>' +
        '</span>' +
      '</li>'
    );

  });
}

/**
 * Fetch Content Ratings by library type and sort from most to fewest
 *
 * @param array     includeLibraryTypes    An array of section types to include in the stats list - Will only accept 'movie' or 'show' types
 * @param array     excludeLibraryIds      An array of section IDs to exclude from the stats list
 * @param string    stats_count            The number of items to list
 */
function getTopContentRatings (includeLibraryTypes = ['movie', 'show'], excludeLibraryIds = [], stats_count = 5) {

  let loadingListItem = '<li class="list-group-item loading"><span>&nbsp;</span></li>';
  let listItem = '<li class="list-group-item">&nbsp;</li>';

  $('#contentRatingTabContent .list-group').append(loadingListItem.repeat(stats_count));

  apiToken = $('#apiTokenData').data('token');
  function getContentRatingBySection (id, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {postData: '/library/sections/' + id + '/contentRating', apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push(result);
      }
    });
  }

  function getContentRatingCount (fastKey, title, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {'postData': fastKey, apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push({
          rating: title,
          count: result.MediaContainer.size
        });
      }
    });
  }

  function buildContentRatingArray (libraryContentRatings) {
    let contentRatingArray = [];
    $.each(libraryContentRatings, function (i, library) {
      $.each(library.MediaContainer.Directory, function (k, contentRating) {
        contentRatingArray.push({
          title: contentRating.title,
          fastKey: contentRating.fastKey
        });
      });
    });
    return contentRatingArray;
  }

  function assembleAndSortContentRatings (contentRatingArray) {
    let mergedContentRatingCounts = {};
    let sortedContentRatingCounts = [];
    $.each(contentRatingArray, function (key, value) {
      if (mergedContentRatingCounts[value.rating]) mergedContentRatingCounts[value.rating] += value.count;
      else mergedContentRatingCounts[value.rating] = value.count;
    });
    for (var prop in mergedContentRatingCounts) {
      sortedContentRatingCounts.push({
        rating: prop,
        count: mergedContentRatingCounts[prop]
      });
    }
    return sortedContentRatingCounts.sort(function (nvp1, nvp2) {
      return nvp2.count - nvp1.count;
    });
  }

  const plexLibraries = queryPlex('/library/sections');

  let buildIdArrays = plexLibraries.then(function (data) {

    let libraryIds = {
      movieLibraryIds: [],
      showLibraryIds: []
    };
    let libraries = data.MediaContainer.Directory;

    $.each(libraries, function (i, library) {
      if (library.type === 'movie' && ((includeLibraryTypes.indexOf('movie')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.movieLibraryIds.push(library.key);
      } else if (library.type === 'show' && ((includeLibraryTypes.indexOf('show')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.showLibraryIds.push(library.key);
      }
    });

    return libraryIds;

  });

  buildIdArrays.then(function (ids) {

    let promiseArray = [];
    let allLibraries = {
      movieLibraries: [],
      showLibraries: []
    };

    if (ids.movieLibraryIds.length) {
      $.each(ids.movieLibraryIds, function (i, id) {
        promiseArray.push(getContentRatingBySection(id, allLibraries.movieLibraries));
      });
    }

    if (ids.showLibraryIds.length) {
      $.each(ids.showLibraryIds, function (i, id) {
        promiseArray.push(getContentRatingBySection(id, allLibraries.showLibraries));
      });
    }

    $.when.apply($, promiseArray).done(function () {

      let contentRatingArray = {
        movies: buildContentRatingArray(allLibraries.movieLibraries),
        shows: buildContentRatingArray(allLibraries.showLibraries)
      };
      let newPromiseArray = [];
      let contentRatingObj = {
        movies: [],
        shows: []
      };

      if (contentRatingArray.movies.length) {
        $.each(contentRatingArray.movies, function (i, rating) {
          newPromiseArray.push(getContentRatingCount(rating.fastKey, rating.title, contentRatingObj.movies));
        });
      }

      if (contentRatingArray.shows.length) {
        $.each(contentRatingArray.shows, function (i, rating) {
          newPromiseArray.push(getContentRatingCount(rating.fastKey, rating.title, contentRatingObj.shows));
        });
      }

      $.when.apply($, newPromiseArray).done(function () {

        $('#contentRatingTabs, #contentRatingTabContent').empty();

        if (contentRatingObj.movies.length && ((includeLibraryTypes.indexOf('movie')) > -1)) {

          let sortedMovieContentRatings = assembleAndSortContentRatings(contentRatingObj.movies);

          $('#contentRatingTabs').append(
            '<li class="nav-item">' +
              '<a class="nav-link active" id="movieRatingTab" data-toggle="tab" href="#movieRatings" role="tab" aria-controls="movieRatings" aria-selected="true">' +
                'Movies' +
              '</a>' +
            '</li>'
          );

          $('#contentRatingTabContent').append(
            '<div id="movieRatings" class="tab-pane show active" role="tabpanel" aria-labelledby="movieRatingTab">' +
              '<ul class="list-group list-group-flush"></ul>' +
            '</div>'
          );

          $.each(sortedMovieContentRatings, function (i, item) {
            $('#movieRatings ul').append(
              '<li class="list-group-item d-flex justify-content-between">' +
                item.rating + '<span>' + item.count.toLocaleString() + '</span>' +
              '</li>'
            );
            return i < (stats_count - 1);
          });

          if (sortedMovieContentRatings.length < stats_count) {
            let emptyListItemCount = stats_count - sortedMovieContentRatings.length;
            $('#movieRatings ul').append(listItem.repeat(emptyListItemCount));
          }

        }

        if (contentRatingObj.shows.length && ((includeLibraryTypes.indexOf('show')) > -1)) {

          let sortedTvShowContentRatings = assembleAndSortContentRatings(contentRatingObj.shows);

          $('#contentRatingTabs').append(
            '<li class="nav-item">' +
              '<a class="nav-link" id="tvShowRatingTab" data-toggle="tab" href="#tvShowRatings" role="tab" aria-controls="tvShowRatings" aria-selected="false">' +
                'TV Shows' +
              '</a>' +
            '</li>'
          );

          $('#contentRatingTabContent').append(
            '<div id="tvShowRatings" class="tab-pane" role="tabpanel" aria-labelledby="tvShowRatingTab">' +
              '<ul class="list-group list-group-flush"></ul>' +
            '</div>'
          );

          $.each(sortedTvShowContentRatings, function (i, item) {
            $('#tvShowRatings ul').append(
              '<li class="list-group-item d-flex justify-content-between">' +
                item.rating + '<span>' + item.count.toLocaleString() + '</span>' +
              '</li>'
            );
            return i < (stats_count - 1);
          });

          if (sortedTvShowContentRatings.length < stats_count) {
            let emptyListItemCount = stats_count - sortedTvShowContentRatings.length;
            $('#tvShowRatings ul').append(listItem.repeat(emptyListItemCount));
          }

          if (!contentRatingObj.movies.length) {
            $('#tvShowRatingTab, #tvShowRatings').addClass('active');
            $('#tvShowRatings').addClass('show');
          }
        }

      });

    });
  });
}

/**
 * Fetch Genres by library type and sort from most to fewest
 *
 * @param array     includeLibraryTypes    An array of section types to include in the stats list - Will only accept 'movie' or 'show' types
 * @param array     excludeLibraryIds      An array of section IDs to exclude from the stats list
 * @param string    stats_count            The number of items to list
 */
function getTopGenres (includeLibraryTypes = ['movie', 'show'], excludeLibraryIds = [], stats_count = 5) {

  let loadingListItem = '<li class="list-group-item loading"><span>&nbsp;</span></li>';

  $('#genreTabContent .list-group').append(loadingListItem.repeat(stats_count));

  function getGenreBySection (id, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {'postData': '/library/sections/' + id + '/genre', apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push(result);
      }
    });
  }

  function getGenreCount (fastKey, title, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {'postData': fastKey, apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push({
          genre: title,
          count: result.MediaContainer.size
        });
      }
    });
  }

  function buildGenreArray (libraryGenres) {
    let genreArray = [];
    $.each(libraryGenres, function (i, library) {
      $.each(library.MediaContainer.Directory, function (k, genre) {
        genreArray.push({
          title: genre.title,
          fastKey: genre.fastKey
        });
      });
    });
    return genreArray;
  }

  function assembleAndSortGenres (genreArray) {
    let mergedGenreCounts = {};
    let sortedGenreCounts = [];
    $.each(genreArray, function (key, value) {
      if (mergedGenreCounts[value.genre]) mergedGenreCounts[value.genre] += value.count;
      else mergedGenreCounts[value.genre] = value.count;
    });
    for (var prop in mergedGenreCounts) {
      sortedGenreCounts.push({
        genre: prop,
        count: mergedGenreCounts[prop]
      });
    }
    return sortedGenreCounts.sort(function (nvp1, nvp2) {
      return nvp2.count - nvp1.count;
    });
  }

  const plexLibraries = queryPlex('/library/sections');

  let buildIdArrays = plexLibraries.then(function (data) {

    let libraryIds = {
      movieLibraryIds: [],
      showLibraryIds: []
    };
    let libraries = data.MediaContainer.Directory;

    $.each(libraries, function (i, library) {
      if (library.type === 'movie' && ((includeLibraryTypes.indexOf('movie')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.movieLibraryIds.push(library.key);
      } else if (library.type === 'show' && ((includeLibraryTypes.indexOf('show')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.showLibraryIds.push(library.key);
      }
    });

    return libraryIds;

  });

  buildIdArrays.then(function (ids) {

    let promiseArray = [];
    let allLibraries = {
      movieLibraries: [],
      showLibraries: []
    };

    if (ids.movieLibraryIds.length) {
      $.each(ids.movieLibraryIds, function (i, id) {
        promiseArray.push(getGenreBySection(id, allLibraries.movieLibraries));
      });
    }

    if (ids.showLibraryIds.length) {
      $.each(ids.showLibraryIds, function (i, id) {
        promiseArray.push(getGenreBySection(id, allLibraries.showLibraries));
      });
    }

    $.when.apply($, promiseArray).done(function () {

      let genreArray = {
        movies: buildGenreArray(allLibraries.movieLibraries),
        shows: buildGenreArray(allLibraries.showLibraries)
      };
      let newPromiseArray = [];
      let genreObj = {
        movies: [],
        shows: []
      };

      if (genreArray.movies.length) {
        $.each(genreArray.movies, function (i, genre) {
          newPromiseArray.push(getGenreCount(genre.fastKey, genre.title, genreObj.movies));
        });
      }

      if (genreArray.shows.length) {
        $.each(genreArray.shows, function (i, genre) {
          newPromiseArray.push(getGenreCount(genre.fastKey, genre.title, genreObj.shows));
        });
      }

      $.when.apply($, newPromiseArray).done(function () {

        $('#genreTabs, #genreTabContent').empty();

        if (genreObj.movies.length && ((includeLibraryTypes.indexOf('movie')) > -1)) {
          $('#genreTabs').append(
            '<li class="nav-item">' +
              '<a class="nav-link active" id="movieGenreTab" data-toggle="tab" href="#movieGenres" role="tab" aria-controls="movieGenres" aria-selected="true">' +
                'Movies' +
              '</a>' +
            '</li>'
          );
          $('#genreTabContent').append(
            '<div id="movieGenres" class="tab-pane show active" role="tabpanel" aria-labelledby="movieGenreTab">' +
              '<ul class="list-group list-group-flush"></ul>' +
            '</div>'
          );
          $.each(assembleAndSortGenres(genreObj.movies), function (i, item) {
            if (item.count > 0) {
              $('#movieGenres ul').append(
                '<li class="list-group-item d-flex justify-content-between">' +
                  item.genre + '<span>' + item.count.toLocaleString() + '</span>' +
                '</li>'
              );
            }
            return i < (stats_count - 1);
          });
        }

        if (genreObj.shows.length && ((includeLibraryTypes.indexOf('show')) > -1)) {
          $('#genreTabs').append(
            '<li class="nav-item">' +
              '<a class="nav-link" id="tvShowGenreTab" data-toggle="tab" href="#tvShowGenres" role="tab" aria-controls="tvShowGenres" aria-selected="false">' +
                'TV Shows' +
              '</a>' +
            '</li>'
          );
          $('#genreTabContent').append(
            '<div id="tvShowGenres" class="tab-pane" role="tabpanel" aria-labelledby="tvShowGenreTab">' +
              '<ul class="list-group list-group-flush"></ul>' +
            '</div>'
          );
          $.each(assembleAndSortGenres(genreObj.shows), function (i, item) {
            if (item.count > 0) {
              $('#tvShowGenres ul').append(
                '<li class="list-group-item d-flex justify-content-between">' +
                  item.genre + '<span>' + item.count.toLocaleString() + '</span>' +
                '</li>'
              );
            }
            return i < (stats_count - 1);
          });
          if (!genreObj.movies.length) {
            $('#tvShowGenreTab, #tvShowGenres').addClass('active');
            $('#tvShowGenres').addClass('show');
          }
        }

      });

    });
  });
}

/**
 * Grabs "Most Popular" movies, based on number of users who have watched
 *
 * @param string    time_range    Default time range in days to grab stats for (30, 90 or 365)
 * @param string    stats_count   The number of items to list
 */
function getPopularMovies (time_range = '30', stats_count = '5') {

  let loadingListItem = '<li class="list-group-item bg-dark loading"><span>&nbsp;</span></li>';

  $('#popMovies').empty().append(loadingListItem.repeat(stats_count));

  const getPopularMovies = queryTautulli({'cmd': 'get_home_stats', 'stats_count': stats_count, 'time_range': time_range});
  let pop_movie_range_value = $('#setPopMovieRange').val();

  getPopularMovies.done(function(data) {

    if (!$.isEmptyObject(data.response.data)) {

      let home_stats = data.response.data;

      $('#popMovies').empty();

      $.each(home_stats, function(i, item) {
        if (item.stat_id === 'popular_movies') {
          $.each(item.rows, function(i, item) {
            $('#popMovies').append(
              '<li class="list-group-item d-flex justify-content-between align-items-center bg-dark">' +
                '<span class="movietooltip longtitle-left" title="' + item.title + '" data-toggle="tooltip">' +
                  item.title +
                '</span>' +
                '<span class="longtitle-right">' +
                  item.users_watched + ' Users ' +
                  '<span class="movietooltip" title="' + item.total_plays + ' Plays" data-toggle="tooltip">' +
                    '<i class="fas fa-fw fa-info-circle"></i>' +
                  '</span>' +
                '</span>' +
              '</li>'
            );
          });
        }
      });

      $('.movietooltip').tooltip();

    } else {

      $('#popMovies').empty().html(
        '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
          '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getMovieStats(' + pop_movie_range_value + ')">' +
            'RELOAD' +
          '</button>' +
        '</li>'
      );

    }
  });

  getPopularMovies.fail(function () {

    $('#popMovies').empty().html(
      '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
        '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getMovieStats(' + pop_movie_range_value + ')">' +
          'RELOAD' +
        '</button>' +
      '</li>'
    );

  });
}

/**
 * Grabs "Most Popular" TV Shows, based on number of users who have watched
 *
 * @param string    time_range    Default time range in days to grab stats for (30, 90 or 365)
 * @param string    stats_count   The number of items to list
 */
function getPopularTvShows (time_range = '30', stats_count = '5') {

  let loadingListItem = '<li class="list-group-item bg-dark loading"><span>&nbsp;</span></li>';

  $('#popTvShows').empty().append(loadingListItem.repeat(stats_count));

  const getPopularTvShows = queryTautulli({'cmd': 'get_home_stats', 'stats_count': stats_count, 'time_range': time_range});
  let popTvRangeValue = $('#setPopTvRange').val();

  getPopularTvShows.done(function (data) {

    if (!$.isEmptyObject(data.response.data)) {

      let home_stats = data.response.data;

      $('#popTvShows').empty();

      $.each(home_stats, function(i, item) {
        if (item.stat_id == 'popular_tv') {
          $.each(item.rows, function(i, item) {
            $('#popTvShows').append(
              '<li class="list-group-item d-flex justify-content-between align-items-center bg-dark">' +
                '<span class="showtooltip longtitle-left" title="' + item.title + '" data-toggle="tooltip">' +
                  item.title +
                '</span>' +
                '<span class="longtitle-right">' +
                  item.users_watched + ' Users ' +
                  '<span class="showtooltip" title="' + item.total_plays + ' Plays" data-toggle="tooltip">' +
                    '<i class="fas fa-fw fa-info-circle"></i>' +
                  '</span>' +
                '</span>' +
              '</li>'
            );
          });
        }
      });

      $('.showtooltip').tooltip();

    } else {

      $('#popTvShows').empty().html(
        '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
          '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getTvStats(' + popTvRangeValue + ')">' +
            'RELOAD' +
          '</button>' +
        '</li>'
      );

    }
  });

  getPopularTvShows.fail(function () {

    $('#popTvShows').empty().html(
      '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
        '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getTvStats(' + popTvRangeValue + ')">' +
          'RELOAD' +
        '</button>' +
      '</li>'
    );

  });
}

/**
 * Grabs "Most Popular" Plex client platforms, based on total plays
 *
 * @param string    time_range    Default time range in days to grab stats for (30, 90 or 365)
 * @param string    stats_count   The number of items to list
 */
function getTopPlatforms (time_range = '30', stats_count = '5') {

  let loadingListItem = '<li class="list-group-item bg-dark loading"><span>&nbsp;</span></li>';

  $('#topPlatforms').empty().append(loadingListItem.repeat(stats_count));

  const getTopPlatforms = queryTautulli({'cmd': 'get_home_stats', 'stats_count': stats_count, 'time_range': time_range});
  let platformRangeValue = $('#setPlatformRange').val();

  getTopPlatforms.done(function (data) {

    if (!$.isEmptyObject(data.response.data)) {

      let platform_stats = data.response.data;

      $('#topPlatforms').empty();

      $.each(platform_stats, function(i, item) {
        if (item.stat_id == 'top_platforms') {
          $.each(item.rows, function(i, item) {
            $('#topPlatforms').append(
              '<li class="list-group-item d-flex justify-content-between align-items-center bg-dark">' +
                '<span class="longtitle-left">' +
                  item.platform +
                '</span>' +
                '<span class="longtitle-right">' +
                  + item.total_plays + ' Plays' +
                '</span>' +
              '</li>'
            );
          });
        }
      });

    } else {

      $('#topPlatforms').empty().html(
        '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
          '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getPlatformStats(' + platformRangeValue + ')">' +
            'RELOAD' +
          '</button>' +
        '</li>'
      );

    }
  });

  getTopPlatforms.fail(function () {

    $('#topPlatforms').empty().html(
      '<li class="list-group-item d-flex h-100 justify-content-center list-group-item-danger align-items-center">' +
        '<button type="button" class="btn btn-outline-dark btn-sm" onclick="getPlatformStats(' + platformRangeValue + ')">' +
          'RELOAD' +
        '</button>' +
      '</li>'
    );

  });
}

/**
 * Fetch Content Ratings, Genres and Years by library type and sort from most to fewest
 * Using the method of grabbing the array size from each tag's fastKey endpoint, the number of calls that need to happen is quite high, particularly for tags such as:
 * ... director, writer, actor, etc
 *
 * @param string    tagType               Accepted values: contentRating, genre, year
 * @param number    statCount             The number of items to list
 * @param array     includeLibraryTypes   An array of section types to include in the stats list - Will only accept 'movie' or 'show' types
 * @param array     excludeLibraryIds     An array of section IDs to exclude from the stats list
 */
function getTopTag (tagType, statCount = 5, includeLibraryTypes = ['movie', 'show'], excludeLibraryIds = []) {

  function getTagBySection (id, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {'postData': '/library/sections/' + id + '/' + tagType, apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push(result);
      }
    });
  }

  function getTagCount (fastKey, title, array) {
    return $.ajax({
      type: 'POST',
      url: 'api.php',
      cache: false,
      data: {'postData': fastKey, apiToken: apiToken},
      dataType: 'json',
      success: function (result) {
        array.push({
          tag: title,
          count: result.MediaContainer.size
        });
      }
    });
  }

  function buildTagArray (libraryTags) {
    let tagArray = [];
    $.each(libraryTags, function (i, library) {
      $.each(library.MediaContainer.Directory, function (k, tag) {
        tagArray.push({
          title: tag.title,
          fastKey: tag.fastKey
        });
      });
    });
    return tagArray;
  }

  function assembleAndSortTags (tagArray) {
    let mergedTagCounts = {};
    let sortedTagCounts = [];
    $.each(tagArray, function (key, value) {
      if (mergedTagCounts[value.tag]) mergedTagCounts[value.tag] += value.count;
      else mergedTagCounts[value.tag] = value.count;
    });
    for (var prop in mergedTagCounts) {
      sortedTagCounts.push({
        tag: prop,
        count: mergedTagCounts[prop]
      });
    }
    return sortedTagCounts.sort(function (nvp1, nvp2) {
      return nvp2.count - nvp1.count;
    });
  }

  const plexLibraries = queryPlex('/library/sections');

  let buildIdArrays = plexLibraries.then(function (data) {

    let libraryIds = {
      movieLibraryIds: [],
      showLibraryIds: []
    };
    let libraries = data.MediaContainer.Directory;

    $.each(libraries, function (i, library) {
      if (library.type === 'movie' && ((includeLibraryTypes.indexOf('movie')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.movieLibraryIds.push(library.key);
      } else if (library.type === 'show' && ((includeLibraryTypes.indexOf('show')) > -1) && (excludeLibraryIds.indexOf(library.key) === -1)) {
        libraryIds.showLibraryIds.push(library.key);
      }
    });

    return libraryIds;

  });

  if (tagType === 'contentRating' || tagType === 'genre' || tagType === 'year') {

    buildIdArrays.then(function (ids) {

      let promiseArray = [];
      let allLibraries = {
        movieLibraries: [],
        showLibraries: []
      };

      if (ids.movieLibraryIds.length) {
        $.each(ids.movieLibraryIds, function (i, id) {
          promiseArray.push(getTagBySection(id, allLibraries.movieLibraries));
        });
      }

      if (ids.showLibraryIds.length) {
        $.each(ids.showLibraryIds, function (i, id) {
          promiseArray.push(getTagBySection(id, allLibraries.showLibraries));
        });
      }

      $.when.apply($, promiseArray).done(function () {

        let tagArray = {
          movies: buildTagArray(allLibraries.movieLibraries),
          shows: buildTagArray(allLibraries.showLibraries)
        };
        let newPromiseArray = [];
        let tagObj = {
          movies: [],
          shows: []
        };

        if (tagArray.movies.length) {
          $.each(tagArray.movies, function (i, tag) {
            newPromiseArray.push(getTagCount(tag.fastKey, tag.title, tagObj.movies));
          });
        }

        if (tagArray.shows.length) {
          $.each(tagArray.shows, function (i, tag) {
            newPromiseArray.push(getTagCount(tag.fastKey, tag.title, tagObj.shows));
          });
        }

        $.when.apply($, newPromiseArray).done(function () {

          console.log('Movies: Top ' + tagType, assembleAndSortTags(tagObj.movies));

          console.log('Shows: Top ' + tagType, assembleAndSortTags(tagObj.shows));

        });

      });
    });

  } else {
    console.log('Incorrect tagType for getTopTag(). Please use "contentRating", "genre" or "year" only.');
  }
}