// Remove old msg form textarea
let txtarea = document.getElementById('send_message_content');
txtarea.value = '';

// Real-Time Message Update
let chatId = document.getElementById('msgList').getAttribute('chatId');
var msgBox = document.getElementById('msgBox');
async function fetchNewMsg(chatId, lastMsgId){
    await fetch('http://localhost:9000/update/'+chatId+'/'+lastMsgId).then(response => {
        if (!response.ok) {
            return(console.log('not 200 :' + response.status));
        }
        return response.text();
    }).then((doc) => {
        
        var parser = new DOMParser();
        var html = parser.parseFromString(doc, 'text/html');
        
        let messageList = html.getElementsByClassName('message-container');
        let container = document.getElementById('msgList');
        for(message of messageList){
            console.log(message)
            let lastMsg = document.getElementsByClassName('message-container')
            lastMsg  = lastMsg[lastMsg.length-1];
            lastMsgId = lastMsg.getAttribute('msgId');
            if(lastMsgId !== message.getAttribute('msgId')){
                container.appendChild(message);
                msgBox.scrollTop = msgBox.scrollHeight;
            }

            
        }
    
    }).catch((error)=>{
        console.log("Error: ", error)
    });
}
setInterval(()=>{
    let lastMsg = document.getElementsByClassName('message-container');
    var lastMsgId = 0
    if(lastMsg.length !== 0){
        lastMsg  = lastMsg[lastMsg.length-1];
        lastMsgId = lastMsg.getAttribute('msgId');
    }
    fetchNewMsg(chatId, lastMsgId)
},500);



msgBox.scrollTop = msgBox.scrollHeight;
msgBox.addEventListener("scroll", event => {
    console.log('scrollTop: ' + msgBox.scrollTop);
}, { passive: true });

