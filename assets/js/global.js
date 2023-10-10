var bgFill = false;

function bookmarksite(title, url)
{
    if (window.sidebar) // firefox
        window.sidebar.addPanel(title, url, "");
    else if (window.opera && window.print) { // opera
        var elem = document.createElement('a');
        elem.setAttribute('href', url);
        elem.setAttribute('title', title);
        elem.setAttribute('rel', 'sidebar');
        elem.click();
    }
    else if (document.all)// ie
        window.external.AddFavorite(url, title);
}

function showHideStatsTab(id)
{
    if ($("currentTab").value.length > 0)
    {
        $($("currentTab").value).style.display = "none";
    }
    $("currentTab").value = id;
    $(id).style.display = "block";
    return false;
}

function showHideTip(ele)
{
    $('.formTip').addClass('hidden');
    $('#' + ele.id + 'Tip').removeClass('hidden');
}

function bytesToSize(bytes, precision)
{
    var kilobyte = 1024;
    var megabyte = kilobyte * 1024;
    var gigabyte = megabyte * 1024;
    var terabyte = gigabyte * 1024;

    if ((bytes >= 0) && (bytes < kilobyte)) {
        return bytes + ' B';

    } else if ((bytes >= kilobyte) && (bytes < megabyte)) {
        return (bytes / kilobyte).toFixed(precision) + ' KB';

    } else if ((bytes >= megabyte) && (bytes < gigabyte)) {
        return (bytes / megabyte).toFixed(precision) + ' MB';

    } else if ((bytes >= gigabyte) && (bytes < terabyte)) {
        return (bytes / gigabyte).toFixed(precision) + ' GB';

    } else if (bytes >= terabyte) {
        return (bytes / terabyte).toFixed(precision) + ' TB';

    } else {
        return bytes + ' B';
    }
}

function humanReadableTime(seconds)
{
    var numhours = Math.floor(((seconds % 31536000) % 86400) / 3600);
    var numminutes = Math.floor((((seconds % 31536000) % 86400) % 3600) / 60);
    var numseconds = Math.floor((((seconds % 31536000) % 86400) % 3600) % 60);

    rs = '';
    if (numhours > 0)
    {
        if (numhours != 1)
        {
            rs += numhours + " "+t('uploader_hours');
        }
        else
        {
            rs += numhours + " "+t('uploader_hour');
        }
        rs += " ";
    }
    if (numminutes > 0)
    {
        if (numminutes != 1)
        {
            rs += numminutes + " "+t('uploader_minutes');
        }
        else
        {
            rs += numminutes + " "+t('uploader_minute');
        }
        rs += " ";
    }
    if (numseconds != 1)
    {
        rs += numseconds + " "+t('uploader_seconds');
    }
    else
    {
        rs += numseconds + " "+t('uploader_second');
    }

    return rs;
}

function browserXHR2Support()
{
    if (new XMLHttpRequest().upload)
    {
        return true;
    }

    return false;
}

function htmlEntities(str)
{
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function createRandomAPIKey(eleId)
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 64; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    $('#'+eleId).val(text);
}