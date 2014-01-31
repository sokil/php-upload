(function($) {

    function uploader($element, options) {
        var self = this;
        
        this.fileInput = $element;
        this.options = options;

        // container
        this.container = this.fileInput.parent()
            .css({overflow: 'hidden'});
    
        if(this.container.css('position') === 'static') {
            this.container.css({'position': 'relative'});
        }
        
        if(this.options.classname) {
            this.container.addClass(this.options.classname);
        }
        
        // button
        this.fileInput
            .change(function() {
                self.uploadFile();
            })
            .css({
                opacity: 0,
                position: 'absolute',
                zIndex: 100,
                top: '0px',
                right: '0px',
                fontSize: '200px',
                padding: '0px',
                margin: '0px',
                cursor: 'pointer'
            });
        
        this.fileInput.appendTo(this.container);
    }

    uploader.prototype = {
        
        VALIDATE_ERROR_SIZE     : 0,
        VALIDATE_ERROR_FORMAT   : 1,

        setOption: function(name, value) {
            this.options[name] = value;
            return this;
        },
        
        setTransport: function(transport) {
            this.options['transport'] = transport;
            return this;
        },
        
        setUploadHandlerUrl: function(url) {
            this.options['uploadHandlerUrl'] = url;
            return this;
        },
        
        // check allowed file size and format
        _validate: function() 
        {
            var file = this.fileInput.get(0).files[0];
            
            // size
            if(this.options.maxSize && file.size > this.options.maxSize) {
                throw this.VALIDATE_ERROR_SIZE;
            }
            
            // format
            if(this.options.supportedFormats.length) {
                var currentFormat = file.name.substr(file.name.lastIndexOf('.') + 1),
                    formatAllowed = false;
            
                for(var i = 0; i < this.options.supportedFormats.length; i++) {
                    if(currentFormat === this.options.supportedFormats[i]) {
                        formatAllowed = true;
                        break;
                    }
                }
                
                if(!formatAllowed) {
                    throw this.VALIDATE_ERROR_FORMAT;
                }
            }
        },
        
        uploadFile: function()
        {
            try {
                this._validate();
            }
            catch(code) {
                this.options.oninvalidfile.call(this, code);
                return;
            }
            
            if(this.options.onbeforeupload.call(this) === false) {
                return;
            }
            // upload
            if(this.options.transport) {
                this['_' + this.options.transport + 'Upload']();
            }
            else {
                try {
                    this._xhrUpload();
                }
                catch(e) {
                    this._iframeUpload();
                }                
            }
            
            return this;
        },

        _xhrUpload: function()
        {            
            var xhr = new XMLHttpRequest();
            
            var file = this.fileInput.get(0).files[0];
            var uri = this._getRequestUri({f: file.name});
            
            var self = this;
            xhr.onreadystatechange = function() {
                if (xhr.readyState !== 4) {
                    return;
                }
                
                try {
                    if(xhr.status !== 200) {
                        throw new Error('Server returns error code ' + xhr.status);
                    }
                    
                    var response = xhr.responseText;
                    if(self.options.responseType === 'json') {
                        response = response
                            ? eval("(" + response + ")")
                            : {};
                    }
                        
                    self.options.onsuccess.call(self, response);
                }
                catch(e) {
                    self.options.onerror.call(self, e.message);
                }
                
                self.options.onafterupload.call(self);
            };

            if('upload' in xhr) {
                xhr.upload.onprogress = function(e) {
                    self.options.onprogress.call(self, e.loaded, e.total);
                };
            }
            

            xhr.open("POST", uri, true);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            xhr.setRequestHeader("X-File-Name", encodeURIComponent(file.fileName));
            xhr.setRequestHeader("Content-Type", "application/octet-stream");
            xhr.send(file); 
            
        },

        _iframeUpload: function()
        {
            // generate X-Progress-ID
            var uuid = "";
            for (var i = 0; i < 32; i++) {
                uuid += Math.floor(Math.random() * 16).toString(16);
            }
            
            // prepare request uri
            var requestUri = this._getRequestUri({
                'X-Progress-ID': uuid
            });
            
            // create iframe
            var $iframe = $('<iframe src="javascript:void(0);" style="display:none;" name="iframeUpload"></iframe>')
                .appendTo(document.body);
        
            var $form = $('<form method="post" enctype="multipart/form-data" action="' + requestUri + '" target="iframeUpload" style="display:none;"/>')
                .appendTo(document.body);
            
            // clone input
            var clonedFileInput = this.fileInput.clone(true),
                fileInputParent = this.fileInput.parent();
            
            // move file input to iframe form
            $(this.fileInput).attr('name', 'f').appendTo($form);

            // add clean file input to old location
            this.fileInput = clonedFileInput.appendTo(fileInputParent);

            var self = this;
            $iframe.load(function() {
                try {
                    var response = $iframe.contents().find('body').html();
                    if(self.options.responseType === 'json') {
                        response = response
                            ? eval("(" + response + ")")
                            : {};
                    }
                        
                    self.options.onsuccess.call(self, response);
                }
                catch(e) {
                    self.options.onerror.call(self, e);
                    
                }
                
                self.options.onafterupload.call(self);

                $iframe.remove();
                $form.remove();
            });
            
            
            // get progress from nginx upload progress module
            var updateProgress = function() {              
                $.get(
                    self.options.progressHandlerUrl,
                    {'X-Progress-ID': uuid},
                    function(responseText) {
                        
                        var response = eval(responseText);
                        
                        switch(response.state)
                        {
                            case 'uploading':
                                self.options.onprogress.call(self, response.received, response.size);
                                setTimeout(updateProgress, 5000);
                                break;
                        }
                            
                    });                
            };
            
            setTimeout(updateProgress, 1000);
            
            // submit form
            $form.submit();
        },

        _getRequestUri: function(additionalParams)
        {
            var uri = this.options.uploadHandlerUrl + '';

            var queryString = [];

            var params = this.options.uploadHandlerParams();
            for(var key in params) {
                queryString.push(key + '=' + encodeURIComponent(params[key]));
            }
            
            if(typeof additionalParams !== 'undefined') {
                for(key in additionalParams) {
                    queryString.push(key + '=' + encodeURIComponent(additionalParams[key]));
                }
            }

            if(queryString !== '') {
                uri += '?' + queryString.join('&');
            }

            return uri;
        }

    };
    
    
    /**
     * jQuery plugin
     */
    $.fn.uploader = function() {
        var $element = $(this);
        
        // init
        if(arguments.length && typeof arguments[0] === 'object') {
            // config
            var options = $.extend({}, {
                transport               : null,   // set upload transport
                progressHandlerUrl      : null,   // only for iframe
                uploadHandlerUrl        : null,
                uploadHandlerParams     : function() {},
                classname               : null,
                onsuccess               : function(response) {},
                onerror                 : function(message) {},
                oninvalidfile           : function(code) {},
                onbeforeupload          : function() {},
                onafterupload           : function() {},
                onprogress              : function(loaded, total) {},
                supportedFormats        : [],
                maxSize                 : null,
                responseType            : 'json'
            }, arguments[0]);
            
            // init
            $element.data('selfInstance', new uploader($element, options));
        }
        // configure initialised
        else {
            
            // check if uploader initialised
            var u = $element.data('selfInstance');
            if(!u) {
                throw new Error('Uploader not initialised');
            }
            
            // return uploader object
            if(!arguments.length) {
                return u;
            }
            
            // call Uploader method
            u[arguments[0]].apply(u, Array.prototype.slice.call(arguments, 1));
        }
        
    };

})(jQuery);