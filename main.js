document.addEventListener("DOMContentLoaded", function(){
    var rightcard = false;
    var tempblock;
    var tempblock2;

    var blocks_type = window.blocks_type;

    /*
    var blocks_type = [
        {
          "category": "Disparadores",
          "name": "Producto comprado ",
          "description": "Dispara una acción según el producto comprado",
          "icon": "fa fa-cart-plus",
          "value": 1
        },
        {
          "category": "Disparadores",
          "name": "Categoria comprada",
          "description": "Dispara una acción según la categoría comprada",
          "icon": "fas fa-list-alt",
          "value": 2
        },
        {
          "category": "Disparadores",
          "name": "Cambio de status de la orden",
          "description": "Dispara una acción cuando el producto cambia de status",
          "icon": "fa fa-exchange-alt",
          "value": 3
        },
        {
          "category": "Disparadores",
          "name": "Una orden presenta una novedad",
          "description": "Dispara una acción cuando una orden presenta una novedad",
          "icon": "fa fa-bell",
          "value": 4
        },
        {
          "category": "Disparadores",
          "name": "Departamento del comprador",
          "description": "Dispara una acción según el producto comprado",
          "icon": "fa fa-map-marked-alt",
          "value": 5
        },
        {
          "category": "Disparadores",
          "name": "Ciudad",
          "description": "Dispara una acción según la ciudad del comprador",
          "icon": "fa fa-map-marker-alt",
          "value": 6
        },
        
        {
          "category": "Acciones",
          "name": "Enviar Email",
          "description": "Envía un email",
          "icon": "fa fa-envelope",
          "value": 7
        },
        
        {
          "category": "Acciones",
          "name": "Enviar WHATSAPP",
          "description": "Envía un mensaje de whatsapp",
          "icon": "fas fa-whatsapp",
          "value": 8
        },
        {
          "category": "Acciones",
          "name": "Cambiar status de la orden",
          "description": "Cambia el status de una orden",
          "icon": "fa fa-exchange-alt",
          "value": 9
        },
        {
          "category": "Condiciones",
          "name": "Decisión(Respuesta Rápida)",
          "description": "Usuario responde con un botón de respuesta rápida",
          "icon": "fa fa-reply",
          "value": 10
        }
      ];
      */

      function createBlockElement(block) {
        var div = document.createElement("div");
        div.className = "blockelem create-flowy noselect";
        
        var input = document.createElement("input");
        input.type = "hidden";
        input.name = "blockelemtype";
        input.className = "blockelemtype";
        input.value = block.value;
      
        var grabmeDiv = document.createElement("div");
        grabmeDiv.className = "grabme";
        
        var imgGrabme = document.createElement("img");
        imgGrabme.src = "assets/grabme.svg";
        
        grabmeDiv.appendChild(imgGrabme);
        
        var blockinDiv = document.createElement("div");
        blockinDiv.className = "blockin";
        
        var blockicoDiv = document.createElement("div");
        blockicoDiv.className = "blockico";
        
        var spanBlockico = document.createElement("span");
        
        var iBlockico = document.createElement("i");
        iBlockico.className = block.icon;
        
        blockicoDiv.appendChild(spanBlockico);
        blockicoDiv.appendChild(iBlockico);
        
        var blocktextDiv = document.createElement("div");
        blocktextDiv.className = "blocktext";
        
        var blocktitleP = document.createElement("p");
        blocktitleP.className = "blocktitle";
        blocktitleP.textContent = block.name;
        
        var blockdescP = document.createElement("p");
        blockdescP.className = "blockdesc";
        blockdescP.textContent = block.description;
        
        blocktextDiv.appendChild(blocktitleP);
        blocktextDiv.appendChild(blockdescP);
        
        blockinDiv.appendChild(blockicoDiv);
        blockinDiv.appendChild(blocktextDiv);
        
        div.appendChild(input);
        div.appendChild(grabmeDiv);
        div.appendChild(blockinDiv);
      
        return div; // Retorna el elemento div creado
      }

      function createBlockElementActive(block) {

            var blockyDiv = "<div class='blockydiv'></div>";
            
            var blockyleftDiv = "<div class='blockyleft'><i class='" + block.icon + " text-primary fa-2x'></i><p class='blockyname'>" + block.name + "</p></div>";
            
            var blockyrightDiv = "<div class='blockyright'><img src='assets/more.svg'></div>";
            
            var blockyinfoDiv = "<div class='blockyinfo px-2'><p>" + block.description + "</p></div>";
        
            return blockyleftDiv + blockyrightDiv + blockyDiv + blockyinfoDiv;
        }
      
      var blockListDiv = document.getElementById("blocklist");
      
      blocks_type.forEach(function(block) {
        if (block.category === "Disparadores") {
          var divBlock = createBlockElement(block);
          blockListDiv.appendChild(divBlock);
        }
      });
      
    //document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/eye.svg"></div><div class="blocktext">                        <p class="blocktitle">Producto Comprado</p><p class="blockdesc">Cuándo el usuario compra cualquier producto o un producto en específico</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="2"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Action is performed</p><p class="blockdesc">Triggers when somebody performs a specified action</p></div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="3"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/time.svg"></div><div class="blocktext">                        <p class="blocktitle">Time has passed</p><p class="blockdesc">Triggers after a specified amount of time</p>          </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="4"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Error prompt</p><p class="blockdesc">Triggers when a specified error happens</p>              </div></div></div>';
    flowy(document.getElementById("canvas"), drag, release, snapping);
    function addEventListenerMulti(type, listener, capture, selector) {
        var nodes = document.querySelectorAll(selector);
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].addEventListener(type, listener, capture);
        }
    }


    function snapping(drag, first) {
        var grab = drag.querySelector(".grabme");
        grab.parentNode.removeChild(grab);
        var blockin = drag.querySelector(".blockin");
        blockin.parentNode.removeChild(blockin);
        var blockelemtype = blocks_type[parseInt(drag.querySelector(".blockelemtype").value)-1];
        //7
        console.log(JSON.stringify(blockelemtype));

        if(first == true && (blockelemtype['category'] == "Disparadores" || blockelemtype['category'] == "Condiciones")){
            drag.innerHTML += createBlockElementActive(blockelemtype);
            //console.log("first: "+first);
            return true;
        }else
        if(first == false && blockelemtype['category'] == "Condiciones"){
            drag.innerHTML += createBlockElementActive(blockelemtype);
            //console.log("first: "+first);
            return true;
        }else
        if(first == false && blockelemtype['category'] != "Disparadores"){
            drag.innerHTML += createBlockElementActive(blockelemtype);
            //console.log("first: "+first);
            return true;
        }else{
            console.log("Sólo un Disparador o Condición pueden ir primero");
            return false;
        }
    }
    
    function drag(block) {
        block.classList.add("blockdisabled");
        tempblock2 = block;
        console.log(block);
    }

    function release() {
        if (tempblock2) {
            tempblock2.classList.remove("blockdisabled");
        }
    }

    var disabledClick = function(){
        document.querySelector(".navactive").classList.add("navdisabled");
        document.querySelector(".navactive").classList.remove("navactive");
        this.classList.add("navactive");
        this.classList.remove("navdisabled");
        if (this.getAttribute("id") == "triggers") {
            //document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/eye.svg"></div><div class="blocktext">                        <p class="blocktitle">Producto Comprado</p><p class="blockdesc">Cuándo el usuario compra cualquier producto o un producto en específico</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="2"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Action is performed</p><p class="blockdesc">Triggers when somebody performs a specified action</p></div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="3"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/time.svg"></div><div class="blocktext">                        <p class="blocktitle">Time has passed</p><p class="blockdesc">Triggers after a specified amount of time</p>          </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="4"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                    <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Error prompt</p><p class="blockdesc">Triggers when a specified error happens</p>              </div></div></div>';
            document.getElementById("blocklist").innerHTML = "";
            blocks_type.forEach(function(block) {
                if (block.category === "Disparadores") {
                  var divBlock = createBlockElement(block);
                  blockListDiv.appendChild(divBlock);
                }
            });
        } else if (this.getAttribute("id") == "actions") {
            //document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="5"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/database.svg"></div><div class="blocktext">                        <p class="blocktitle">Envío de WhatsApp</p><p class="blockdesc">Adds a new entry to a specified database</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="6"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/database.svg"></div><div class="blocktext">                        <p class="blocktitle">Update database</p><p class="blockdesc">Edits and deletes database entries and properties</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="7"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/action.svg"></div><div class="blocktext">                        <p class="blocktitle">Perform an action</p><p class="blockdesc">Performs or edits a specified action</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="8"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/twitter.svg"></div><div class="blocktext">                        <p class="blocktitle">Make a tweet</p><p class="blockdesc">Makes a tweet with a specified query</p>        </div></div></div>';
            document.getElementById("blocklist").innerHTML = "";
            blocks_type.forEach(function(block) {
                if (block.category === "Acciones") {
                  var divBlock = createBlockElement(block);
                  blockListDiv.appendChild(divBlock);
                }
            });
        } else if (this.getAttribute("id") == "loggers") {
            //document.getElementById("blocklist").innerHTML = '<div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="9"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/log.svg"></div><div class="blocktext">                        <p class="blocktitle">Add new log entry</p><p class="blockdesc">Adds a new log entry to this project</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="10"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/log.svg"></div><div class="blocktext">                        <p class="blocktitle">Update logs</p><p class="blockdesc">Edits and deletes log entries in this project</p>        </div></div></div><div class="blockelem create-flowy noselect"><input type="hidden" name="blockelemtype" class="blockelemtype" value="11"><div class="grabme"><img src="assets/grabme.svg"></div><div class="blockin">                  <div class="blockico"><span></span><img src="assets/error.svg"></div><div class="blocktext">                        <p class="blocktitle">Prompt an error</p><p class="blockdesc">Triggers a specified error</p>        </div></div></div>';
            document.getElementById("blocklist").innerHTML = "";
            blocks_type.forEach(function(block) {
                if (block.category === "Condiciones") {
                  var divBlock = createBlockElement(block);
                  blockListDiv.appendChild(divBlock);
                }
            });
        }
    }

    addEventListenerMulti("click", disabledClick, false, ".side");

    document.getElementById("close").addEventListener("click", function(){
       if (rightcard) {
           rightcard = false;
           document.getElementById("properties").classList.remove("expanded");
           setTimeout(function(){
                document.getElementById("propwrap").classList.remove("itson"); 
           }, 300);
            tempblock.classList.remove("selectedblock");
       } 
    });

    
    document.getElementById("removeblock").addEventListener("click", function(){
        //flowy.deleteBlocks();
        window.location.href = 'tabla_automatizadores.php?id_configuracion='+window.id_configuracion;
    });

    //back
    document.getElementById("back").addEventListener("click", function(){
        window.location.href = 'tabla_automatizadores.php?id_configuracion='+window.id_configuracion;
    });

    
    
    if (window.flowlyOutputBlocks) {
        function generateHTML(jsonInput) {
            const inputObj = JSON.parse(jsonInput);
    
            // Generar los elementos de bloque
            const blockElements = inputObj.blocks.map(block => {
                const blockType = blocks_type.find(bt => bt.value == block.data.find(data => data.name === 'blockelemtype').value);
                if (!blockType) {
                    console.error('Block type not found for block:', block);
                    return '';
                }
                const blockStyle = block.attr.find(attr => attr.style) ? block.attr.find(attr => attr.style).style : '';
                const blockElemTypeValue = block.data.find(data => data.name === 'blockelemtype') ? block.data.find(data => data.name === 'blockelemtype').value : '';
                const blockIdValue = block.data.find(data => data.name === 'blockid') ? block.data.find(data => data.name === 'blockid').value : '';
    
                const indicator = block.id === 0 ? `<div class="indicator invisible" style="left: 154px; top: 134px;"></div>` : '';
    
                return `<div class="blockelem noselect block" style="${blockStyle}">
                            <input type="hidden" name="blockelemtype" class="blockelemtype" value="${blockElemTypeValue}">
                            <input type="hidden" name="blockid" class="blockid" value="${blockIdValue}">
                            <div class="blockyleft">
                                <i class="${blockType.icon} text-primary fa-2x" aria-hidden="true"></i>
                                <p class="blockyname">${blockType.name}</p>
                            </div>
                            <div class="blockyright">
                                <img src="assets/more.svg">
                            </div>
                            <div class="blockydiv"></div>
                            <div class="blockyinfo px-2">
                                <p>${blockType.description}</p>
                            </div>
                            ${indicator}
                        </div>`;
            }).join('');
    
            // Generar los elementos de flecha
            const arrowElements = inputObj.blockarr.map((block, index) => {
                if (block.parent !== -1) {
                    const parentBlock = inputObj.blockarr.find(b => b.id === block.parent);
                    if (!parentBlock) {
                        console.error('Parent block not found for block:', block);
                        return '';
                    }
    
                    const parentCenterX = parentBlock.x + (parentBlock.width / 2);
                    const parentBottomY = parentBlock.y + parentBlock.height;
    
                    const childCenterX = block.x + (block.width / 2);
                    const childTopY = block.y;
    
                    const arrowLeft = Math.min(parentCenterX, childCenterX);
                    const arrowWidth = Math.abs(childCenterX - parentCenterX);
                    const arrowHeight = childTopY - parentBottomY;
    
                    const arrowPath = `M${parentCenterX - arrowLeft} 0L${parentCenterX - arrowLeft} ${arrowHeight / 2}L${childCenterX - arrowLeft} ${arrowHeight / 2}L${childCenterX - arrowLeft} ${arrowHeight}`;
    
                    return `<div class="arrowblock" style="left: ${arrowLeft}px; top: ${parentBottomY}px; width: ${arrowWidth}px; height: ${arrowHeight}px; visibility: hidden;">
                                <input type="hidden" class="arrowid" value="${index}">
                                <svg preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="${arrowPath}" stroke="#C5CCD0" stroke-width="2px"></path>
                                    <path d="M${childCenterX - arrowLeft - 5} ${arrowHeight - 5}H${childCenterX - arrowLeft + 5}L${childCenterX - arrowLeft} ${arrowHeight}L${childCenterX - arrowLeft - 5} ${arrowHeight - 5}Z" fill="#C5CCD0"></path>
                                </svg>
                            </div>`;
                }
                return '';
            }).join('');
    
            return {
                html: blockElements + arrowElements
            };
        }
    
        //console.log(window.flowlyOutputBlocks);
    
        const inputJSON = JSON.stringify(window.flowlyOutputBlocks);
    
        const generatedHTML = generateHTML(inputJSON);
    
        const inputObj = JSON.parse(inputJSON);
    
        const outputObj = {
            html: generatedHTML.html,
            /* html: inputObj.html, */
            blockarr: inputObj.blockarr,
            blocks: inputObj.blocks
        };
    
        const outputJSON = JSON.stringify(outputObj);
    
        console.log(outputObj);
    
        if (outputObj.blockarr && outputObj.blocks && outputObj.html) {
            try {
                flowy.import(outputObj);
            } catch (error) {
                console.error('Error during flowy.import:', error);
            }
        } else {
            console.error('Invalid structure in outputObj:', outputObj);
        }
    }
    
    
    var aclick = false;
    var noinfo = false;

    var beginTouch = function (event) {
        aclick = true;
        noinfo = false;
        if (event.target.closest(".create-flowy")) {
            noinfo = true;
        }
    }

    var checkTouch = function (event) {
        aclick = false;
    }

    var doneTouch = function (event) {
        //console.log(event);
        if (event.type === "mouseup" && aclick && !noinfo) {

            /*
            if (rightcard) {
                rightcard = false;
                document.getElementById("properties").classList.remove("expanded");
                document.getElementById("propwrap").classList.remove("itson");
                tempblock.classList.remove("selectedblock");
            } 
            */

            if (!rightcard && event.target.closest(".block") && !event.target.closest(".block").classList.contains("dragging")) {
                    tempblock = event.target.closest(".block");

                    // Acceder al valor del input con nombre "blockid" dentro del bloque seleccionado
                    var valorBlockID = tempblock.querySelector('input[name="blockid"]').value;
                    // blockelemtype
                    var valorTipoID = tempblock.querySelector('input[name="blockelemtype"]').value;

                    // Imprimir el valor en la consola
                    console.log("Valor del input 'blockid' del bloque seleccionado:");
                    console.log(valorBlockID);

                    // Reemplazar el texto dentro de #proplist con el valor del input 'blockid'
                    var proplist = document.getElementById("proplist");

                    // JSON de Selects Múltiples
                    const selectMultipleOptions = window.selectMultipleOptions;

                    /*
                    function generateSelectOptions(id, selectedOptions) {
                        let options = selectMultipleOptions[id];
                        let html = options.map((option, index) => {
                            const isSelected = selectedOptions.includes(option.id) ? "selected" : "";
                            return `<option value="${option.id}" ${isSelected}>${option.id} - ${option.text}</option>`;
                        }).join('');
                        return html;
                    }
                    */

                    // Obtiene el ID de automatizador desde la URL
                    function getAutomatizadorIdFromUrl() {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get('id_automatizador');
                    }

                    function generateSelectOptions(id, selectedOptions) {
                        let options = selectMultipleOptions[id];
                        let html = options.map((option, index) => {
                            const isSelected = selectedOptions.includes(option.id) ? "selected" : "";
                            return `<option value="${option.id}" ${isSelected}>${option.id} - ${option.text}</option>`;
                        }).join('');
                        return html;
                    }


                    console.log("El resultado del anterior formulario es: " + JSON.stringify(formDataByBlock[valorBlockID]));

                    var codigoHTML = `
                        <h4 id="header2">${blocks_type[valorTipoID - 1]['name']}</h4>
                        <form class="text-left" id="myForm">
                        <input type="hidden" name="id_block" value="${valorBlockID}">
                    `;
                    
                    // Producto
                    if (valorTipoID == "1" || valorTipoID == "3") {
                        var nameInput = "productos[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="productos">Productos</label>
                                <select multiple class="form-control select2" id="productos" name="${nameInput}">
                                    ${generateSelectOptions('Productos', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Categoria
                    if (valorTipoID == "2") {
                        var nameInput = "categorias[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="categorias">Categorias</label>
                                <select multiple class="form-control select2" id="categorias" name="${nameInput}">
                                    ${generateSelectOptions('Categorias', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Status
                    if (valorTipoID == "3") {
                        var nameInput = "status[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="status">Status</label>
                                <select multiple class="form-control select2" id="status" name="${nameInput}">
                                    ${generateSelectOptions('Status', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Novedad
                    if (valorTipoID == "4") {
                        var nameInput = "novedad[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="novedad">Novedad</label>
                                <select multiple class="form-control select2" id="novedad" name="${nameInput}">
                                    ${generateSelectOptions('Novedad', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Provincia
                    if (valorTipoID == "5") {
                        var nameInput = "provincia[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="provincia">Provincia</label>
                                <select multiple class="form-control select2" id="provincia" name="${nameInput}">
                                    ${generateSelectOptions('Provincia', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Ciudad
                    if (valorTipoID == "6") {
                        var nameInput = "ciudad[]";
                        const selectedOptions = formDataByBlock[valorBlockID] && formDataByBlock[valorBlockID][nameInput];
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="ciudad">Ciudad</label>
                                <select multiple class="form-control select2" id="ciudad" name="${nameInput}">
                                    ${generateSelectOptions('Ciudad', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }
                    
                    
                    // Asunto
                    if (valorTipoID == "7") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="selectInsertAsunto">Insertar Campo Asunto</label>
                                <select id="selectInsertAsunto" class="form-control">
                                    <option value="">Insertar Campo Asunto</option>
                                    <option value="{{nombre}}">{{nombre}}</option>
                                    <option value="{{direccion}}">{{direccion}}</option>
                                    <option value="{{email}}">{{email}}</option>
                                    <option value="{{celular}}">{{celular}}</option>
                                    <option value="{{order_id}}">{{order_id}}</option>
                                </select>
                            </div>
                            <div class="form-group col-12 p-2">
                                <label for="asunto">Asunto</label>
                                <input type="text" class="form-control" id="asunto" name="asunto" placeholder="Asunto" value="${formDataByBlock[valorBlockID]?.asunto || ''}">
                            </div>
                        `;
                    }
                    
                    //id_whatsapp_message_template 
                    if (valorTipoID == "8") {
                        const selectedOptions = formDataByBlock[valorBlockID]?.id_whatsapp_message_template || '';
                
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="mensaje">Plantillas de Mensajes</label>
                                <select class="form-control" id="id_whatsapp_message_template" name="id_whatsapp_message_template" onchange="updateMessage()">
                                    ${generateSelectOptions('id_whatsapp_message_template', selectedOptions || [])}
                                </select>
                            </div>
                        `;
                    }

                    // Mensaje Email
                    if (valorTipoID == "7") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="selectInsertMensaje">Insertar Campo Mensaje</label>
                                <select id="selectInsertMensaje" class="form-control">
                                    <option value="">Insertar Campo Mensaje</option>
                                    <option value="{{nombre}}">{{nombre}}</option>
                                    <option value="{{direccion}}">{{direccion}}</option>
                                    <option value="{{email}}">{{email}}</option>
                                    <option value="{{celular}}">{{celular}}</option>
                                    <option value="{{order_id}}">{{order_id}}</option>
                                </select>
                            </div>
                            <div class="form-group col-12 p-2">
                                <label for="mensaje">Mensaje</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="3" placeholder="Mensaje">${formDataByBlock[valorBlockID]?.mensaje || ''}</textarea>
                                <p>Reemplaza las variables entre doble llaves por CAMPOS DINÁMICOS para el envío del email</p>
                            </div>
                        `;
                    }
                    
                    // Mensaje WhatsApp
                    if (valorTipoID == "8") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="selectInsertMensaje">Insertar Campo Mensaje</label>
                                <select id="selectInsertMensaje" class="form-control">
                                    <option value="">Insertar Campo Mensaje</option>
                                    <option value="{{nombre}}">{{nombre}}</option>
                                    <option value="{{direccion}}">{{direccion}}</option>
                                    <option value="{{email}}">{{email}}</option>
                                    <option value="{{celular}}">{{celular}}</option>
                                    <option value="{{order_id}}">{{order_id}}</option>
                                </select>
                            </div>
                            <div class="form-group col-12 p-2">
                                <label for="mensaje">Mensaje</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="3" placeholder="Mensaje">${formDataByBlock[valorBlockID]?.mensaje || ''}</textarea>
                                <p>Reemplaza las variables entre doble llaves de las plantillas de facebook por CAMPOS DINÁMICOS</p>
                            </div>
                        `;
                    }

                    // Agregar este script después de agregar todo el contenido HTML en la página
                    setTimeout(function() {
                        // Asunto
                        var selectInsertAsunto = document.getElementById('selectInsertAsunto');
                        if (selectInsertAsunto) {
                            selectInsertAsunto.addEventListener('change', function() {
                                var selectValue = this.value;
                                if (!selectValue) return; // Si no se selecciona nada, no hacer nada.

                                var asuntoField = document.getElementById('asunto');

                                if (asuntoField) {
                                    asuntoField.value += selectValue;
                                }

                                // Resetear el valor del select después de la inserción
                                this.value = "";
                            });
                        }

                        // Mensaje
                        var selectInsertMensaje = document.getElementById('selectInsertMensaje');
                        if (selectInsertMensaje) {
                            selectInsertMensaje.addEventListener('change', function() {
                                var selectValue = this.value;
                                if (!selectValue) return; // Si no se selecciona nada, no hacer nada.

                                var mensajeField = document.getElementById('mensaje');

                                if (mensajeField) {
                                    mensajeField.value += selectValue;
                                }

                                // Resetear el valor del select después de la inserción
                                this.value = "";
                            });
                        }
                    }, 0); // Ejecutar después de que el HTML esté en el DOM

                    // Opciones de Mensaje
                    /*
                    if (valorTipoID == "8") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="opciones_mensaje">Opciones de Mensaje</label>
                                <input type="text" class="form-control mb-2" id="mensaje_opcion_1" name="mensaje_opcion_1" placeholder="Opción 1" value="${formDataByBlock[valorBlockID]?.mensaje_opcion_1 || ''}">
                                <input type="text" class="form-control mb-2" id="mensaje_opcion_2" name="mensaje_opcion_2" placeholder="Opción 2" value="${formDataByBlock[valorBlockID]?.mensaje_opcion_2 || ''}">
                                <input type="text" class="form-control mb-2" id="mensaje_opcion_3" name="mensaje_opcion_3" placeholder="Opción 3" value="${formDataByBlock[valorBlockID]?.mensaje_opcion_3 || ''}">
                            </div>
                        `;
                    }
                    

                    // tiempo Envío Mensaje
                    if (valorTipoID == "7" || valorTipoID == "8") {
                        codigoHTML += `
                            <div class="form-group p-2">
                                <label for="tiempo_envio">Tiempo Envío Mensaje</label>
                                    <input type="number" class="form-control" id="tiempo_envio" name="tiempo_envio" placeholder="Tiempo Envío Mensaje" value="${formDataByBlock[valorBlockID]?.tiempo_envio || ''}">
                                    <select class="form-control" id="tipo_envio" name="tipo_envio">
                                        <option value="m" ${formDataByBlock[valorBlockID]?.tipo_envio === 'm' ? 'selected' : ''}>Minutos</option>
                                        <option value="h" ${formDataByBlock[valorBlockID]?.tipo_envio === 'h' ? 'selected' : ''}>Horas</option>
                                    </select>
                            </div>
                        `;
                    }

                    // tiempo re-Envío Mensaje
                    if (valorTipoID == "8") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="tiempo_reenvio">Tiempo Re-Envío Mensaje</label>
                                <input type="number" class="form-control" id="tiempo_reenvio" name="tiempo_reenvio" placeholder="Tiempo Re-Envío Mensaje" value="${formDataByBlock[valorBlockID]?.tiempo_reenvio || ''}">
                                <select class="form-control" id="tipo_reenvio" name="tipo_reenvio">
                                    <option value="m" ${formDataByBlock[valorBlockID]?.tipo_reenvio === 'm' ? 'selected' : ''}>Minutos</option>
                                    <option value="h" ${formDataByBlock[valorBlockID]?.tipo_reenvio === 'h' ? 'selected' : ''}>Horas</option>
                                </select>
                            </div>
                        `;
                    }

                    // veces de reenvío mensaje hasta que conteste mensajes
                    if (valorTipoID == "8") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="veces_reenvio">Veces de Reenvío</label>
                                <input type="number" class="form-control" id="veces_reenvio" name="veces_reenvio" placeholder="Veces de Reenvío Mensaje" value="${formDataByBlock[valorBlockID]?.veces_reenvio || ''}">
                            </div>
                        `;
                    }
                    */

                    // Texto a Recibir - Condicion
                    if (valorTipoID == "10") {
                        codigoHTML += `
                            <div class="form-group col-12 p-2">
                                <label for="texto_recibir">Condicion de Texto a Recibir</label>
                                <input type="text" class="form-control" id="texto_recibir" name="texto_recibir" placeholder="Condicion de Texto a Recibir" value="${formDataByBlock[valorBlockID]?.texto_recibir || ''}">
                            </div>
                        `;
                    }

                    codigoHTML += `
                        <button type="button" class="btn btn-primary pt-2 w-100" onclick="obtenerValoresFormulario()">Guardar</button>
                    </form>
                    `;

                    proplist.innerHTML = codigoHTML;

                    // Asegúrate de que este código se ejecute después de que el DOM esté listo
                    $(document).ready(function() {
                        $('.select2').select2({
                            placeholder: "Selecciona una opción",
                            allowClear: true
                        });
                    });

                    rightcard = true;
                    document.getElementById("properties").classList.add("expanded");
                    document.getElementById("propwrap").classList.add("itson");
                    tempblock.classList.add("selectedblock");
            } 
        }
    }

    
    addEventListener("mousedown", beginTouch, false);
    addEventListener("mousemove", checkTouch, false);
    addEventListener("mouseup", doneTouch, false);
    addEventListenerMulti("touchstart", beginTouch, false, ".block");
    
    /*
    // Obtener el elemento closecard
    var closecard = document.getElementById('closecard');
    // Obtener el elemento opencard
    var opencard = document.getElementById('leftcard2');
    // Obtener el elemento leftcard
    var leftcard = document.getElementById('leftcard');

    // Agregar un evento de clic al elemento closecard
    closecard.addEventListener('click', function() {
    // Ocultar el elemento leftcard al hacer clic en closecard
    leftcard.style.display = 'none';
    opencard.style.display = '';
    });
    */

});