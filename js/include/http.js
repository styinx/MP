/**
 * Performs a http request and executes the callback function after the coressponding script
 * answers.
 * @param target	path to the target script
 * @param header	request header as array of 2 entries
 * @param params	a list containg the data as key value pair which will be sent
 * @param json		a boolean that defines that the result will be parsed as json
 * @param callback	the function that will be executed after the script answers
 * @param param		an optional parameter which will be transfered to the callback function
 *					if it is required.
 */
function http(target, method = "", header = "", params = {}, json = true, callback = null, param = null)
{
	if(target != "")
	{
		var data = null;
		var request = new XMLHttpRequest();

		if(method != "POST")
		{
			request.open("GET", target, true);
		}
		else
		{
			request.open("POST", target, true);
		}

		if(header != null || header != "")
		{
			if(header == "default")
			{
				request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			}
			else
			{
				request.setRequestHeader("Content-Type", header);
			}
		}

		if(params != {})
		{
			data = "";
			for(var p in params)
			{
				data += p + "=" + params[p] + "&";
			}
			data = data.substring(0, data.length - 1);
		}

		request.send(data);
		request.onreadystatechange = function()
			{
				if(request.readyState == 4 && request.status == 200)
				{
					var result = request.responseText;
					if(callback != null)
					{
						if(json == true)
						{
							result = JSON.parse(result);
						}
						if(param != null)
						{
							callback(result, param);
						}
						else
						{
							callback(result);
						}
					}
				}
			}
	}
}