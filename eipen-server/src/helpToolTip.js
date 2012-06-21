var xoffset=-60 //Customize x offset of tooltip
var yoffset=20 //Customize y offset of tooltip
var dom=document.getElementById && !document.all
var showTip=false


function showToolTip(text) {
	var winObj=document.getElementById("toolTip")
	winObj.innerHTML=text
	showTip=true
	return false
}

function adjustPosition(e){
	if (showTip){
		var curX=e.pageX;
		var curY=e.pageY;

		alert("X: "+curX+" Y:"+curY)

		var winObj=document.getElementById("toolTip")
		winObj.style.left=curX+xoffset+"px"
		winObj.style.top=curY+yoffset+"px"
		winObj.style.visibility="visible"
	}
}

function hideToolTip(){
	showTip=false

	var winObj=document.getElementById("toolTip")
	winObj.innerHTML=""
	winObj.style.visibility="hidden"
	winObj.style.left="-1000px"
	winObj.style.width=''
}

document.onmousemove=adjustPosition
