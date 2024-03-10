let chatId = document.getElementById('msgList').getAttribute('chatId');

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
            container.appendChild(message);
        }
    
    }).catch((error)=>{
        console.log("Error: ", error)
    });
}
setInterval(()=>{
    let lastMsg = document.getElementsByClassName('message-container');
    lastMsg  = lastMsg[lastMsg.length-1];
    let lastMsgId = lastMsg.getAttribute('msgId');
    fetchNewMsg(chatId, lastMsgId)
    document.body.scrollTop = document.body.scrollHeight;
},500);

