var remote_url = 'http://douchat.cc/';

function get_api_url() {
    return remote_url;
}

function get_api_data(type,callback,params) {
    var data = {};
    data['callback'] = callback;
    if (params) {
        data['params'] = params;
    }
    $.ajax({
        url:remote_url+'?/interface/'+type+'/',
        dataType: 'jsonp', 
        data: data,
        jsonp:'callback', 
    });
}