var lastSearch = new Date();

function saveSession()
{
    var data = {"request" : "saveSession", "session" : JSON.stringify(MP)};
    var callback = function(response)
    {
        document.getElementById('status').innerHTML = response;
    };
    http("php/source/script.php", "POST", "default", data, false, callback, null);
}

function loadSession()
{
    var data = {"request" : "loadSession"};
    var callback = function(response)
    {
        MP = JSON.parse(response);
    };
    http("php/source/script.php", "POST", "default", data, false, callback, null);
}

function setupLibrary()
{
    var data = {"request" : "setupLibrary"};
    var callback = function(response)
    {
        document.getElementById('status').innerHTML = response;
    };
    http("php/source/script.php", "POST", "default", data, false, callback, null);
}

function loadLibrary()
{
    var data = {"request" : "loadLibrary"};
    var callback = function(response)
    {
        document.getElementById('library').innerHTML = response;
        $('#library').tablesorter();
    };
    http("php/source/script.php", "POST", "default", data, false, callback, null);
}

function reloadLibrary()
{
    if(lastSearch.getTime() < new Date().getTime() - 500)
    {
        var data = {"request" : "loadLibrary", "query" : document.getElementById('query').value};
        var callback = function(response)
        {
            document.getElementById('library').innerHTML = response;
            $('#library').tablesorter();
        };
        lastSearch = new Date();
        http("php/source/script.php", "POST", "default", data, false, callback, null);
    }
}

function loadMedia(element)
{
    MP.element = element;
    if(MP.media.paused)
    {
        MP.element = element;
        MP.artist = MP.element.getElementsByClassName('artist')[0].innerHTML;
        MP.title = MP.element.getElementsByClassName('title')[0].innerHTML;
        MP.album = MP.element.getElementsByClassName('album')[0].innerHTML;

        var data = {"request": "loadMedia", "media": MP.element.getElementsByClassName('file')[0].innerHTML};
        var callback = function (response) {

            MP.load("data:audio/mp3;base64," + response);

            document.getElementsByClassName('media-progress')[0].max = MP.media.duration;

            $(MP.element).addClass("selected").siblings().removeClass("selected");

            document.getElementsByClassName('music-player-nav-title')[0].innerHTML =
                MP.artist + " - " + MP.title + " [" + timeformat(MP.media.duration) + "]";
            MP.playback();
        };
        http("php/source/script.php", "POST", "default", data, false, callback, null);
    }
}

/* update UI containers when values changed */

function updateUI()
{
    document.getElementsByClassName('media-volume')[0].value = MP.volume;
    document.getElementsByClassName('media-progress')[0].value = MP.media.currentTime;
    document.getElementById('time-expired').innerHTML = timeformat(MP.media.currentTime);
    document.getElementById('time-left').innerHTML = timeformat(MP.media.duration - MP.media.currentTime);
    if(MP.media.duration !== 0)
    {
        document.getElementsByClassName('media-progress')[0].max = MP.media.duration;
        document.getElementsByClassName('music-player-nav-title')[0].innerHTML =
            MP.artist + " - " + MP.title + " [" + timeformat(MP.media.duration) + "]";
    }
    if(MP.media.paused)
    {
        document.getElementById('playback').style.backgroundImage = "url('resources/icons/play.png')";
    }
    else
    {
        document.getElementById('playback').style.backgroundImage = "url('resources/icons/pause.png')";
    }
}

/* Key Events */

window.onkeydown = function (e)
{
    var code = e.keyCode ? e.keyCode : e.which;

    // +
    if (code === 107)
    {
        MP.setVolume(MP.volume + 0.01);
    }
    // -
    else if (code === 109)
    {
        MP.setVolume(MP.volume - 0.01);
    }
    // Space
    else if (code === 32)
    {
        MP.playback();
    }
};

/* Context Menu */

$(document).bind("contextmenu", function (e)
{
    if($(e.target).is(".track *"))
    {
        e.preventDefault();
        $(".context-menu").finish().toggle(100).css({
            top: e.pageY + "px",
            left: e.pageX + "px"
        });
    }
});

$(document).bind("mousedown wheel", function (e)
{

    if (!$(e.target).parents(".context-menu").length > 0) {

        $(".context-menu").hide(100);
    }
});


$(".context-menu .submenu").click(function()
{

    $(".custom-menu").hide(100);
});


/* support function */

function timeformat(time) {
    var sec_num = parseInt(time, 10);
    if (sec_num >= 0)
    {
        var hours = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (hours < 10) {
            hours = "0" + hours;
        }
        if (minutes < 10) {
            minutes = "0" + minutes;
        }
        if (seconds < 10) {
            seconds = "0" + seconds;
        }

        if (hours === "00")
            return minutes + ':' + seconds;
        else
            return hours + ':' + minutes + ':' + seconds;
    }
    else
    {
        return "00:00";
    }
}