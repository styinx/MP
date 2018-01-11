var MP =
{
    media : null,
    artist : "",
    title : "",
    album : "",
    duration : 0,
    file : null,
    content : null,
    buffer : null,
    volume : 0.1,
    progress : 0,
    mute : false,
    shuffle : true,
    repeat : false,
    element : null,

    PL :
    {
        previousIndex : null,
        currentIndex : null,
        nextIndex : null,

    },
    init : function(media)
    {
        this.media = media;
        this.media.volume = this.volume;
    },
    load : function(base64)
    {
        this.content = base64;
        if(this.media.paused)
        {
            this.media.src = this.content;
            this.media.onended = function()
            {
                MP.nextTrack();
            };
        }
    },
    // loadMedia : function(element)
    // {
    //     this.element = element;
    //     loadMedia(file);
    //     this.playback();
    // },
    playback : function()
    {
        if(this.media.paused)
        {
            this.media.play();
            this.media.ontimeupdate = updateUI;
        }
        else
        {
            this.media.pause();
        }
        updateUI();
    },
    previousTrack : function()
    {
        if(this.media.paused)
        {
            if(MP.shuffle)
            {
                var sibl = MP.element.parentElement.childNodes;
                var next = sibl[Math.floor(Math.random() * sibl.length)];
                loadMedia(next)
            }
            else
                loadMedia(MP.element.previousSibling)
        }
        else
        {
            this.playback();
            this.nextTrack();
        }
    },
    nextTrack : function()
    {
        if(this.media.paused)
        {
            if(MP.shuffle)
            {
                var sibl = MP.element.parentElement.childNodes;
                var next = sibl[Math.floor(Math.random() * sibl.length)];
                loadMedia(next)
            }
            else
                loadMedia(MP.element.nextSibling)
        }
        else
        {
            this.playback();
            this.nextTrack();
        }
    },
    setVolume : function(vol)
    {
        if(vol >= 0 && vol <= 1)
        {
            this.volume = vol;
            this.media.volume = this.volume;
            updateUI();
        }
    },
    setProgress : function(prog)
    {
        if(prog >= 0 && prog <= this.media.duration)
        {
            this.progress = prog;
            this.media.currentTime = this.progress;
            updateUI();
        }
    }
};