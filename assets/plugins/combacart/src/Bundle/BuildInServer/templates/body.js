const loadDeferredStyles = function () {
    const addStylesNode = document.getElementById("deferred-styles");
    const replacement = document.createElement("div");
    replacement.innerHTML = addStylesNode.textContent;
    document.body.appendChild(replacement)
    addStylesNode.parentElement.removeChild(addStylesNode);
};
const raf = window.requestAnimationFrame || window.mozRequestAnimationFrame ||
    window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;
if (raf) raf(function () {
    window.setTimeout(loadDeferredStyles, 0);
});
else window.addEventListener('load', loadDeferredStyles);

class CombaStorageClass {
    set(key, value) {
        let t = typeof value;
        if (t === 'undefined' || value === null || value === '') {
            sessionStorage.removeItem(key);
        } else {
            sessionStorage.setItem(key, (t === 'object') ? JSON.stringify(value) : value);
        }
        return value;
    }

    get(key) {
        const obj = sessionStorage.getItem(key);
        try {
            const j = JSON.parse(obj);
            if (j && typeof j === "object") return j;
        } catch (e) {
        }
        return obj;
    }

    unique(key, field) {
        let obj = this.get(key);
        if (typeof obj !== "object") return null;
        let uniqueArray = [];
        for (const [i, v] of obj.entries()) {
            if (!uniqueArray.find(x => x[field] === obj[i][field])) {
                uniqueArray.push(obj[i]);
            }
        }
        this.set(key, uniqueArray);
    }

    remove(key, field, value) {
        let obj = this.get(key);
        if (typeof obj !== "object") return null;
        let result = [];
        for (const i in obj) {
            if (obj[i][field] !== value) {
                result.push(obj[i]);
            }
        }
        this.set(key, result);
    }

    clear(key) {
        return sessionStorage.removeItem(key);
    }

    push(key, value) {
        let result = [];
        let obj = this.get(key);
        if (typeof value !== "object") return null;
        if (typeof obj !== "object") obj = [];
        for (let i in obj) {
            result.push(obj[i]);
        }
        result.push(value);
        let ret_txt = JSON.stringify(result);
        return this.set(key, ret_txt);
    }

    pop(key) {
        let currentElem = '';
        let obj = this.get(key);
        if (typeof obj !== "object") return null;
        if (!obj) return null;
        currentElem = obj.pop();
        let ret_txt = JSON.stringify(obj);
        this.set(key, ret_txt);
        return currentElem;
    }

    last(key) {
        let obj = this.get(key);
        if (typeof obj !== "object") return null;
        return obj[obj.length - 1];
    }
}

const iAjaxTimeout = 15000;

combaStorage = new CombaStorageClass();

function makeName(names) {
    let name = '';
    if (names) {
        for (let counter = 0; counter < names.length; counter++) {
            if (names[counter]) {
                if (name === '') name = names[counter];
                else name = name + '-' + names[counter];
            }
        }
    }
    return name;
}

function searchInTable(obj) {
    if (!obj.table) return;

    const value_s = $('#inptSearch').val();
    let data_s = value_s ? value_s.toUpperCase().split(" ") : null;

    let value_sl2 = null;

    let data_sl2 = value_sl2 ? value_sl2.toUpperCase().split(",") : null;

    let rows = $(obj.table).find("tbody tr").hide();
    if (!data_sl2 && !data_s) {
        rows.show();
        return;
    }

    rows.filter(function (i, v) {
        if (!data_sl2) return true;
        let $t = $(this);
        for (let d = 0; d < data_sl2.length; ++d) {
            if ($t.text().toUpperCase().indexOf(data_sl2[d]) > -1) {
                return true;
            }
        }
        return false;
    }).filter(function (i, v) {
        if (!data_s) return true;
        let $t = $(this);
        for (let d = 0; d < data_s.length; ++d) {
            if ($t.text().toUpperCase().indexOf(data_s[d]) > -1) {
                return true;
            }
        }
        return false;
    }).show();

    checkTableRows();
}

function setStatebtnClear(elem) {
    if (!elem) return;
    const btnclear = $(elem).siblings('.btnclear');

    if ($(elem).val() && $(elem).val().length >= 1) {
        $(btnclear).removeClass('d-none');
        if ($(elem).val().length < 4) {
            $("#btnSearch").addClass('d-none');
        } else {
            $("#btnSearch").removeClass('d-none');
            $("#btnSearch").find('i').removeClass('bi-refresh').addClass('bi-search');
        }
    } else {
        $(btnclear).addClass('d-none');
        $("#btnSearch").removeClass('d-none');
        $("#btnSearch").find('i').removeClass('bi-search').addClass('bi-arrow-clockwise');
    }

    checkTableRows();
}

function openOrder(obj) {
    if (!obj.title) obj.title = '№' + obj.number + ' ' + obj.name;
    const idSprdlg = showSpinner('{{ lang.waitforresult }}');
    showFormInModal(
        {
            oper: 'order',
            uid: obj.uid,
            title: obj.title,
            closeOnModal: false,
            modalcls: 'modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable',
            onOpen: function (event, ui) {
                hideSpinner(idSprdlg);
            },
            onClose: function (event, ui) {
                console.log(obj.uid);
            }
        }
    );
}

function openMessenger(obj) {
    showFormInModal(
        {
            oper: 'messenger',
            uid: obj.document,
            title: '{{ lang.messages }}',
            modalcls: 'modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable',
        }
    );
}

function openTracking(obj) {
    showFormInModal(
        {
            oper: 'tracking',
            uid: obj.document,
            title: '{{ lang.action_tracking }}',
            modalcls: 'modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable',
        }
    );
}

function showFormInModal(options) {

    const dlgID = getTrash();

    const p = document.getElementById("modaldialog");
    let md = p.cloneNode(true);
    md.id = dlgID;

    if (options.modalcls) {
        $(md).find('.modal-dialog').removeClass().addClass(options.modalcls + ' modal-dialog ms-auto');
    }
    $(md).appendTo("body");

    let currentLocation = window.location;
    const hash = currentLocation + '/' + options.oper + '/' + options.uid + '/' + dlgID;

    $(md).addClass('datacontainer').attr('data-docuid', options.uid);
    $(md).addClass('datacontainer').attr('data-dlgid', dlgID);
    $(md).find('.modal-header .modal-title').prepend(options.title);
    $(md).find('.modal-body').load(hash, function (response, status, xhr) {
        if (isJson(response)) {
            Modify(dlgID, false);
            _dlgclose({dlgID: dlgID});
            __responseAsMessage($.parseJSON(response));
            return;
        }
        $(md).find('.modal-header').append($(md).find('.headermenu'));
        $(md).find('.maingroup').append($(md).find('.mainbtnclose'));
        const bb = $(md).find('.bottombar').html();
        $(md).find('.modal-footer').append(bb);
        $(md).find('.bottombar').remove();
    });
    $(md).on('shown.bs.modal', function (e) {
        if (options.onOpen) options.onOpen.call();

        if (isHistoryEnabled()) {
            const state = {'dlg_id': dlgID};
            history.pushState(state, '', window.location.pathname);
        }
    });
    $(md).on('hidden.bs.modal', function (e) {
        if (options.onClose) options.onClose.call();
    });
    const myModal = new bootstrap.Modal(document.getElementById(dlgID), {
        keyboard: false
    });
    myModal.show();
}

function dlgSave(obj) {
    if (obj.dlgID && IsModify(obj.dlgID)) {
        showConfirm({
            txt: "<b>{{ lang.action_save }}</b>",
            callback: function (e) {
                if (e) {
                    const idSprdlg = showSpinner('{{ lang.process_save }}');
                    if (obj.sfd) {
                        let s_sfd = window[obj.sfd]();
                        if (s_sfd.formdata) obj.formdata = s_sfd.formdata;
                        if (s_sfd.sendtype) obj.sendtype = s_sfd.sendtype;
                        if (s_sfd.callback) obj.callback = s_sfd.callback;
                    } else {
                        obj.formdata = btoa(encodeURIComponent($("#form-" + obj.dlgID).serialize({checkboxesAsBools: true})));
                        obj.sendtype = 'saveorder';
                    }
                    saveFormData({
                        formdata: obj.formdata,
                        sendtype: obj.sendtype,
                        uid: obj.uid,
                        callback: function (response) {
                            hideSpinner(idSprdlg);
                            if (response.result === "ok") {
                                Modify(obj.dlgID, false);
                                __responseAsMessage(response);
                                const k = combaStorage.get(makeName([obj.dlgID, 'parent']));
                                if (k) Modify(k, true);
                                if (obj.callback) obj.callback(obj.dlgID);
                            } else {
                                __responseAsError(response);
                            }
                        }
                    });

                } else if (obj.callback) obj.callback(obj.dlgID);
            }
        });
    } else {
        if (obj.callback) obj.callback(obj.dlgID);
    }
}

function saveFormData(obj) {
    $.ajax({
        url: window.location + '/' + obj.sendtype + '/' + obj.uid + '/' + new Date().getTime(),
        type: "POST",
        dataType: 'html',
        data: {
            formdata: obj.formdata
        },
        timeout: iAjaxTimeout,
        success: function (response) {
            if (isJson(response)) {
                response = $.parseJSON(response);
            }
            if (obj.callback) obj.callback(response);
        },
        error: function (jqXHR, exception) {
            let msg = '';
            if (exception === 'timeout') {
                msg = 'Перевищено час очікування відповіді від сервера.';
            } else if (jqXHR.status === 0) {
                msg = 'Немає підключення.\n Перевірте мережу.';
            } else if (jqXHR.status === 404) {
                msg = 'Запитувану сторінку не знайдено. [404]';
            } else if (jqXHR.status === 500) {
                msg = 'Внутрішня помилка сервера [500].';
            } else if (exception === 'parsererror') {
                msg = 'Запитуваний розбір JSON не вдалося виконати.';
            } else if (exception === 'timeout') {
                msg = 'Перевищено час очікування відповіді від сервера.';
            } else if (exception === 'abort') {
                msg = 'Запит ajax перервано.';
            } else {
                msg = 'Невизначена помилка.\n' + jqXHR.responseText;
            }
            if (obj.callback) obj.callback({"message": msg});
        },
    });
}

function getTrash() {
    return Math.random().toString(36).substring(2, 7);
}

function isObject(obj) {
    return obj !== null && typeof obj === 'object';
}

function isJson(item) {
    item = typeof item !== "string"
        ? JSON.stringify(item)
        : item;
    try {
        item = JSON.parse(item);
    } catch (e) {
        return false;
    }
    return typeof item === "object" && item !== null;
}

function isHistoryEnabled() {
    return window.history !== undefined &&
        window.history !== null &&
        window.history.pushState !== undefined &&
        window.history.pushState !== null;
}

function IsModify(name) {
    return combaStorage.get(makeName(['document', name, 'modify'])) === 'true';
}

function Modify(name, statusmodify) {
    if (combaStorage.get(makeName(['document', name, 'readonly'])) === '1' && statusmodify === true) return;
    combaStorage.set(makeName(['document', name, 'modify']), statusmodify);
    combaStorage.set(makeName(['document', name, 'modifytime']), Date.now());
    if (statusmodify) {
        $("#" + name + " #btnsave").addClass("button").removeClass("disabled").prop('disabled', false);
    } else {
        $("#" + name + " #btnsave").addClass("disabled").removeClass("button").prop('disabled', true);
    }
}

function __closeInfo(el) {
    if (el && el.id) {
        $('#' + el.id).find('.dlgspinnerclose').click();
    } else $('.dlgspinnerclose').click();
}

function hideSpinner(obj = null) {
    __closeInfo(obj);
}

function dlgClose(obj) {
    const dlg = $('#' + obj.dlgID).attr('id');
    if (!dlg) {
        console.log('Warning: dlgClose empty ID');
        return;
    }
    if (IsModify(obj.dlgID)) {
        showConfirm({
            txt: '<b>{{ lang.action_close }}</b>',
            callback: function (e) {
                if (e) {
                    dlgSave({
                        dlgID: obj.dlgID,
                        sfd: obj.sfd,
                        uid: obj.uid,
                        callback: function (e) {
                            _dlgclose(obj);
                            if (obj.cfb) {
                                window[obj.cfb]();
                            }
                        }
                    });
                }
            }
        });
    } else {
        _dlgclose(obj);
    }
}

function _dlgclose(obj) {
    const myModalEl = document.getElementById(obj.dlgID);
    const myModal = bootstrap.Modal.getInstance(myModalEl);
    if (myModal) {
        myModal.hide();
        myModal.dispose();
    }

    $('#' + obj.dlgID).remove();
    if (myModal) myModalEl.remove();
}

function __responseAsMessage(response) {
    if (response.msg) {
        __responseAsMsg(response);
    } else {
        if (response.result === 'ok') {
            if (response.message) showSpinner(response.message);
            hideSpinner();
        } else {
            __responseAsError(response);
        }
    }
}

function __responseAsMsg(response) {
    let ar = [];
    if (response.msg && response.msg[0]) {
        ar = response.msg;
    } else {
        ar[0] = response.msg;
    }
    for (let msg of ar) {
        if (msg.type === 'info') showInfo({txt: msg.text, timeout: msg.timeout});
        if (msg.type === 'warning') showErrorModal(msg.text);
        if (msg.type === 'error') showError(msg.text);
        if (msg.type === 'element') {
            if (msg.element) {
                msg.element.forEach(function (elm) {
                    const el = elm.e ? $(elm.e) : null;
                    if (el) {
                        let isActive = false;
                        if ($(el).hasClass('active')) {
                            isActive = true;
                        }
                        if (elm.html) {
                            el.replaceWith(elm.html);
                            if (isActive) $(elm.e).addClass('active');
                        }
                        if (elm.v) el.text(elm.v);
                        if (elm.t) el.attr('title', elm.t);
                        if (elm.rcl) {
                            el.removeClassRegex(elm.rcl);
                        }
                        if (elm.acl) el.addClass(elm.acl);
                        if (elm.cl) el.class(elm.cl);
                    }
                });
            }
        }
    }
}

function __responseAsError(response) {
    if (response.msg) {
        __responseAsMsg(response);
    } else {
        if (isJson(response)) {
            if (response.modal_error) {
                showError(response.message);
            } else {
                showErrorModal(response.message);
            }
        } else {
            if (response.length < 1) {
                showErrorModal('Помилка. Порожня відповідь від серверу');
            } else showError(response);
        }
    }
}

function copyToClipboard(pSource) {
    const copyText = document.getElementById(pSource);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    if (navigator.clipboard) {
        navigator.clipboard.writeText(copyText.value);
    }
}

function ActivateGroup_v3(id, group) {
    if (!group) return;
    if ($("#" + id).is(':checked')) {
        $("#" + group).removeAttr("disabled");
        $("#" + group).focus();
    } else {
        $("#" + group).attr("disabled", "true");
    }
}

function onChangeInputBox_v3(id, dlgid) {
    let ro = $("#" + id).attr('readonly');
    if (ro) {
        Modify(dlgid, true);
        return;
    }

    let i = $("#" + id).attr("maxlength");
    let value = $("#" + id).val();

    if (!value) {
        n = 0;
        $("#" + id + "_send").prop("disabled", true);
    } else {
        n = value.length;
        $("#" + id + "_send").prop("disabled", false);
    }

    if ($("#" + id + "_counter").length > 0) {
        $("#" + id + "_counter").show();
        $("#" + id + "_counter").text((i - n) + "/" + i);
    }

    if (value.length >= i) return false;
    Modify(dlgid, true);
}

function checkTableRows() {
    const allRowsHidden = $('.doc_request tbody tr').filter(':visible').length === 0;

    if (allRowsHidden) {
        $('.td-none').show(); // Відображаємо div, якщо всі рядки приховані
        $('.pg-info').hide();
    } else {
        $('.td-none').hide(); // Ховаємо div, якщо є видимі рядки
        $('.pg-info').show();
    }
}

document.addEventListener("DOMContentLoaded", function (event) {

    $(document).ready(function (jQuery) {

        setStatebtnClear($("#inptSearch"));

        $.fn.removeClassRegex = function (pattern) {

            $(this).each(function (key, element) {
                if (element.length === 0) return;
                const classNames = $(element).prop('class').split(' ');
                $(classNames).each(function (key, value) {
                    if (value.match(pattern)) {
                        $(element).removeClass(value);
                    }
                });
            });
        }

        $(document).on('shown.bs.modal', '.modal', function (event) {
            let zIndex = 1040 + (10 * $('.modal:visible').length);
            $(this).css('z-index', zIndex);
            setTimeout(function () {
                $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
            }, 0);
        });

        let $contextMenuPrint = $("#dlgprint");
        $(document).on("mousedown", ".toprint", function (e) {
            $(this).after($contextMenuPrint);
        });

        $(document).on('click', '.dropdown-item[data-tpl]', function () {
            const tpl = $(this).attr('data-tpl');
            const uid = $(this).parents('.datacontainer').attr('data-docuid');
            let hash = uid + '/' + tpl;
            showSpinner('{{ lang.processing }}');
            $.ajax({
                url: window.location + '/print/' + hash,
                method: 'GET',
                success: function (response) {
                    $('#printContent').html(response);
                    $('#printModal').modal('show');
                    hideSpinner();
                },
                error: function () {
                    $('#printContent').html('{{ lang.template_load_failed }}');
                    hideSpinner();
                }
            });
        });

        // Друк форми
        $(document).on('click', '.btnprint', function () {
            const printContent = $('#printContent').html();
            const newWin = window.open('', '_blank');
            newWin.document.write('<html><head><title>Друк форми</title>');
            newWin.document.write('</head><body>');
            newWin.document.write(printContent);
            newWin.document.write('</body></html>');
            newWin.document.close();
            newWin.print();
        });

        $(document).on('click', '.btnmessenger', function (e) {
            e.preventDefault();
            const docuid = $(this).closest('.datacontainer').attr('data-docuid');
            openMessenger({document: docuid});
        });

        $(document).on('click', '.btntracking', function (e) {
            e.preventDefault();
            const docuid = $(this).closest('.datacontainer').attr('data-docuid');
            openTracking({document:docuid});
        });

        $(document).on("click", ".btnclear", function () {
            let target = $(this).attr('data-target');
            if (target) {
                target = target.split(',');
                $.each(target, function (i, v) {
                    $('#' + v).val('').keyup();
                });
            }
            checkTableRows();
        });

        $(document).on("click", "#btnSearch", function () {
            let hash = window.location.href;
            let str = $('#inptSearch').val();
            if (str.length > 3) {
                showSpinner('{{ lang.processing }}');
                hash = window.location + '/search/' + btoa(encodeURIComponent(str));
                $('#doc_request tbody').load(hash, function (response, status, xhr) {
                    checkTableRows();
                    hideSpinner();
                });
            } else {
                $('#inptSearch').val('');
                window.location = window.location.href;
            }
        });

        $(document).on("keyup", "#inptSearch", function (e) {
            setStatebtnClear(this);
            if ($(this).attr('id') === 'inptSearch') {
                const key = e.which;
                if (key === 13) {
                    if (this.value.length >= 4 || !this.value.length) {
                        $("#btnSearch").click();
                        return;
                    }
                }
                const tdnamesort = $(".table.doc_request");
                if (tdnamesort) {
                    searchInTable({table: tdnamesort});
                }
            }
        });

        $(document).on("click", ".btnpaging", function () {
            let str = $('#inptSearch').val();
            if (str.length > 3) {
                searchandpaging(str,$(this).attr('data-value'));
            } else {
                searchandpaging('-',$(this).attr('data-value'));
            }
        });

        function searchandpaging(search,page){
            showSpinner('{{ lang.processing }}');
            const hash = window.location + '/search/' + btoa(encodeURIComponent(search)) + '/' + page;
            $('#doc_request tbody').load(hash, function (response, status, xhr) {
                checkTableRows();
                hideSpinner();
            });
        }

        $(document).on('click', '.table .selectabled', function (e) {
            if (e.target.nodeName !== 'TD') {
                $(this).addClass('active').siblings().removeClass('active');
                return;
            }

            if ($(this).hasClass('active')) {
                $(this).removeClass('active');
                return;
            }
            $(this).addClass('active').siblings().removeClass('active');
        });

        $(document).on('dblclick', '.opener', function () {
            $(this).closest('tr').siblings().removeClass('active');
            $(this).closest('tr').addClass('active');
            const docuid = $(this).closest('tr').attr('data-docuid');
            if (!docuid) return;

            const docnumber = $(this).closest('tr').find('td:eq(0)').text();

            let docname = $(this).closest('tr').find('td:eq(3)').text();
            docname = docname.split(',')[0];
            openOrder({uid: docuid, number: docnumber, name: docname});
        });

        $(document).on('click', '#btnclose', function (e) {
            e.preventDefault();
            const dlgid = $(this).parents('.datacontainer').attr('data-dlgid');
            const docuid = $(this).parents('.modal-content').find('form').attr('data-docuid');
            dlgClose({dlgID: dlgid, uid: docuid});
        });

        $(document).on('click', '#btnsave', function (e) {
            e.preventDefault();
            const dlgid = $(this).parents('.modal-content').find('form').attr('data-dlgid');
            const docuid = $(this).parents('.modal-content').find('form').attr('data-docuid');
            const sfd = $(this).attr('data-sfd');
            dlgSave({dlgID: dlgid, sfd: sfd, uid: docuid});
        });

        $(document).on('change keypress keyup click', '.inputcounter', function (e) {
            if ($(this).attr('readonly')) return;
            onChangeInputBox_v3(this.id, $(this).attr('data-dlgid'));
        });

    });
});
