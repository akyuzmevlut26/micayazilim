function initPagination(meta) {
    $('#custom-pagination .page-item').not('.page-item:first, .page-item:last').remove();

    let url = window.location.origin + window.location.pathname;

    let urlParameters = urlParams();
    delete urlParameters.page;

    let queryParams = objectToUrlQuery(urlParameters);
    url = queryParams.trim().length ? (url + queryParams + '&') : '?';

    let pages = [];
    for (let i = 1; i <= meta.total_page; i++) {
        pages.push(sprintf('<li class="page-item"><a class="page-link %s" href="%s">%s</a></li>', [(meta.current_page === i ? 'active' : ''), (url + 'page=' + i) , i]));
    }

    $("#page-previous-item").after(pages.join('\n'));

    if (meta.current_page > 1) {
        $("#page-previous-item").removeClass('disabled');
        $("#page-previous-item .page-link").attr('href', (url + 'page=' + (meta.current_page - 1)));
    }

    if ((meta.current_page + 1) <= meta.total_page) {
        $("#page-next-item").removeClass('disabled');
        $("#page-next-item .page-link").attr('href', (url + 'page=' + (meta.current_page + 1)));
    }
}

function sprintf(pattern, params) {
    let i = 0;
    return pattern.replace(/%(s|d|0\d+d)/g, function (x, type) {
        let value = params[i++];
        switch (type) {
            case 's':
                return value;
            case 'd':
                return parseInt(value, 10);
            default:
                value = String(parseInt(value, 10));
                let n = Number(type.slice(1, -1));
                return '0'.repeat(n).slice(value.length) + value;
        }
    });
}


function objectToUrlQuery(object, defaulValue = '', prefix = '?') {
    let parameters = [];

    for(let key in object) {
        let parameter = object[key];

        if(Array.isArray(parameter)) {
            parameter.forEach((data) => {
                parameters.push([(key + '[]'), data])
            });
        } else {
            parameters.push([key, parameter]);
        }
    }

    return (parameters.length ? (prefix + decodeURIComponent(new URLSearchParams(parameters).toString().toString())) : defaulValue);
}

function urlParams(key, defaultValue = null) {
    let urlSearchParams = new URLSearchParams(window.location.search);
    const params = {};

    for (const key of urlSearchParams.keys()) {
        let regex = new RegExp(/\[]+$/)

        if(regex.test(key)) {
            params[key.replace(/\[]+$/, "")] = urlSearchParams.getAll(key);
        } else {
            params[key] = urlSearchParams.get(key);
        }
    }

    if(!key) {
        for (let paramKey in params) {
            let value = params[paramKey];

            if(Array.isArray(value)) {
                params[paramKey] = value.map((data) => {
                    return urlParamValidateValue(data);
                });
            } else {
                params[paramKey] = urlParamValidateValue(value);
            }
        }

        return params;
    }

    if(params[key]) {
        return params[key];
    }

    return defaultValue;
}

function urlParamValidateValue(value) {
    let newValue = value;

    if(['true', 'false'].includes(value)) {
        newValue = value === 'true';
    }

    if(value.trim().length && !isNaN(value)) {
        newValue = parseInt(value)
    }

    return newValue
}
