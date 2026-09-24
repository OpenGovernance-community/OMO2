window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/admin-edit-crop.js"] = function (pageConfig, pageScript) {
(function(){

                $(function () {
                    var imgContainer = $(("#imgContainer_" + pageConfig.key + ""));
                    var img = $(("#myImage_" + pageConfig.key + ""));
                    var img1 = document.getElementById(("myImage_" + pageConfig.key + ""));
                    var imgWidth = (img1 ? img1.naturalWidth : 0);
                    var imgHeight = (img1 ? img1.naturalHeight : 0);
                    var imageDataInput = $(("#imageDataInput_" + pageConfig.key + ""));
                    var zoomSlider = $(("#zoomSlider_" + pageConfig.key + ""));
                    var zoomValue = 1;
                    var oldZoomValue = 1;
                    var exportMime = 'image/jpeg';

                    function resolveExportFormat(source) {
                        var normalizedSource = String(source || '').toLowerCase();
                        if (normalizedSource.indexOf('image/png') !== -1 || /\.png(?:[?#].*)?$/.test(normalizedSource)) {
                            return {
                                mime: 'image/png',
                                extension: 'png'
                            };
                        }

                        return {
                            mime: 'image/jpeg',
                            extension: 'jpg'
                        };
                    }

                    function setExportFormat(source) {
                        var format = resolveExportFormat(source);
                        exportMime = format.mime;
                    }

                    setExportFormat($(("#" + pageConfig.key + "")).val() || (img1 ? img1.currentSrc || img1.src : ''));

                    if (imgHeight == 0) {
                        imgContainer.css("display", "none");
                        zoomSlider.css("display", "none");
                    }

                    var containerWidth = imgContainer.width();
                    var containerHeight = imgContainer.height();
                    var maxZoom = Math.min(imgWidth / containerWidth, imgHeight / containerHeight);

                    function updateCoords() {
                        console.log(("updateCoords_" + pageConfig.key + ""));

                        var imgPosX = parseInt(img.css('left'));
                        var imgPosY = parseInt(img.css('top'));

                        var scale = (maxZoom * zoomValue / 10);

                        var x1 = -imgPosX * scale;
                        var y1 = -imgPosY * scale;
                        var width = imgContainer.width() * scale;
                        var height = imgContainer.height() * scale;

                        var canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img[0], x1, y1, width, height, 0, 0, width, height);

                        // 🔥 Convert to blob
                        canvas.toBlob(function (blob) {

                            window.croppedImages = window.croppedImages || {};
                            window.croppedImages[("" + pageConfig.key + "")] = blob;

                            $(("#" + pageConfig.key + "")).val("newimage");

                            console.log("Blob prêt pour image :", blob);

                        }, exportMime, 0.84);
                    }

                    function updateImg() {
                        console.log(("updateImg_" + pageConfig.key + ""));
                        //	if (img.position().left>0) img.css('left', 0 + 'px');
                        //	if (img.position().top>0) img.css('top', 0 + 'px');

                        var imgSize = imgWidth / (maxZoom * zoomValue / 10);

                        img.css('width', imgSize + 'px');

                        // Clamp image position

                        var imgPosX = parseInt(img.css('left'));
                        var imgPosY = parseInt(img.css('top'));
                        if (imgPosX > 0) {
                            imgPosX = 0;
                        }
                        if (imgPosY > 0) {
                            imgPosY = 0;
                        }
                        //console.log ("3: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());
                        if (imgPosX < imgContainer.width() - img.width()) {
                            imgPosX = imgContainer.width() - img.width();
                        }
                        if (imgPosY < imgContainer.height() - img.height()) {
                            imgPosY = imgContainer.height() - img.height();
                        }
                        //console.log ("4: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());

                        img.css('left', imgPosX + 'px');
                        img.css('top', imgPosY + 'px');
                        updateCoords();
                    }

                    // Event handler for the "Select from disk" button
                    $(("#imageFileInput_" + pageConfig.key + "")).on('change', function (event) {
                        console.log(("#imageFileInput_" + pageConfig.key + ".change()"));
                        var file = event.target.files[0];
                        setExportFormat(file ? file.type : '');
                        var reader = new FileReader();
                        reader.onload = function (event) {
                            // Remove existing image
                            img.remove();
                            img = $(("<img id=\"myImage_" + pageConfig.key + "\" style=\"display: block;position: absolute;top: 0;\tleft: 0; object-fit: contain;\">")).attr('src', event.target.result).appendTo(imgContainer);
                            img.on('load', function () {
                                var img1 = document.getElementById(("myImage_" + pageConfig.key + ""));
                                imgWidth = img1.naturalWidth;
                                imgHeight = img1.naturalHeight;
                                if (imgHeight > 0) {
                                    imgContainer.css("display", "");
                                    zoomSlider.css("display", "");
                                }
                                var containerWidth = imgContainer.width();
                                var containerHeight = imgContainer.height();
                                maxZoom = Math.min(imgWidth / containerWidth, imgHeight / containerHeight);
                                zoomSlider.val(0);
                                var mini = 10 / maxZoom;
                                oldZoomValue = zoomValue = Math.pow(10 - mini, (100 - zoomSlider.val()) / 50 - 1) + mini;

                                // Center image
                                var cx = (containerWidth - (imgWidth / (maxZoom * zoomValue / 10))) * 0.5;  // -0.5 * (container - scaled img width)
                                var cy = (containerHeight - (imgHeight / (maxZoom * zoomValue / 10))) * 0.5;
                                img.css('left', cx + 'px');
                                img.css('top', cy + 'px');

                                updateImg();
                            });
                        };
                        reader.readAsDataURL(file);
                    });

                    // Event handler for the zoom slider
                    zoomSlider.on('input', function () {
                        console.log(("zoomSlider_" + pageConfig.key + ".input"));
                        var mini = 10 / maxZoom;
                        zoomValue = Math.pow(10 - mini, (100 - zoomSlider.val()) / 50 - 1) + mini;

                        // Recenter image position for zoom
                        var imgPosX = parseInt(img.css('left'));
                        var imgPosY = parseInt(img.css('top'));
                        var imgPosX2 = -(imgPosX - containerWidth / 2) * (maxZoom * oldZoomValue / 10);
                        var imgPosY2 = -(imgPosY - containerHeight / 2) * (maxZoom * oldZoomValue / 10);
                        img.css('left', (containerWidth / 2 - (imgPosX2 / (maxZoom * zoomValue / 10))) + 'px');
                        img.css('top', (containerHeight / 2 - (imgPosY2 / (maxZoom * zoomValue / 10))) + 'px');

                        // Save the latest version
                        oldZoomValue = zoomValue;
                        updateImg();
                    });

                    // Event handler for mouse movement on the image
                    imgContainer.on('mousedown', function (event) {
                        console.log(("imgContainer_" + pageConfig.key + ".mousedown"));
                        event.preventDefault();
                        var startX = event.clientX;
                        var startY = event.clientY;
                        var imgPosX = parseInt(img.css('left'));
                        var imgPosY = parseInt(img.css('top'));
                        var moveHandler = function (event) {
                            event.preventDefault();
                            var deltaX = event.clientX - startX;
                            var deltaY = event.clientY - startY;
                            img.css('left', imgPosX + deltaX + 'px');
                            img.css('top', imgPosY + deltaY + 'px');
                            //updateCoords();
                        };
                        var upHandler = function (event) {
                            event.preventDefault();
                            updateCoords();
                            updateImg();
                            $(document).off('mousemove', moveHandler);
                            $(document).off('mouseup', upHandler);
                        };
                        $(document).on('mousemove', moveHandler);
                        $(document).on('mouseup', upHandler);
                    });
                });
            
})();
};
