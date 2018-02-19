
function onbody()
{
	window.onresize=resize;
	resize();
}

function resize()	// Set font relative to window width.
{
    var f_Factor=1;
    var W = window.innerWidth || document.body.clientWidth;

    if (W <= 800) return;
//	P =  Math.floor (W/38);				// ca. 3 percent constant
	P =  Math.floor (f_Factor*(8+W/160));		// Linear function
	if (P<12)P=12;					// Smallest size.
	document.body.style.fontSize=P + 'px';
}

function do_timeout()
{
	self.location.href="/logout.php";
}

function getObj(name)
{
	if (document.getElementById)
        return document.getElementById(name);
	if (document.all)
        return document.all[name].style;
	if (document.layers)
        return document.layers[name];
}

function cancelSettings()
{
	self.location.href="<? echo $_SERVER['SCRIPT_NAME']; ?>?refresh=1";
}

function isFieldBlank(s)
{
    if (!s.trim().length) return true;
	
	return false;
}

function isFieldAscii(s)
{
    if( /[^a-zA-Z0-9.-]/.test(s) )
       return false;

    return true;     
}

function isInRange(n, min, max)
{
	if ( n > max || n < min ) return false;
	return true;
}

function isDecimal(num)
{

    if( /^-?[0-9]+$/.test(num) )
       return true;

    return false; 
}

function isIPValid(ip)
{
    var ipaddress = ip.value;
    var patt = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    var match = ipaddress.match(patt);

    if (ipaddress == "0.0.0.0") {
        return false;
    }

    if (ipaddress == "255.255.255.255") {
        return false;
    }

    if (match == null) {
        return false;
    }
       
    return true;
}

function isNetmaskValid(mask)
{
    subnet = mask.value;
    var patt = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    var match1 = subnet.match(patt);

    if (match1 == null) {
        return false;
    }

    return true;
}

function isMacVaild(mac)
{
    if (!(mac.value.match(/^[0-9a-fA-F:]+$/)) || mac.value.length != 17)  {
        return false;
    }
    return true;
}

function isSubnetSame(fip, sip, mask)
{
    var aa = fip.split('.');
    var ab = sip.split('.');
    if (aa[0]+aa[1]+aa[2] == ab[0]+ab[1]+ab[2])
        return false;

    return true;
}

