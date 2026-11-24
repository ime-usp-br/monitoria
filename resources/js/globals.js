import $ from 'jquery';

window.$ = window.jQuery = $;
window.baseURL = window.location.origin;

const load = async () => {
    await import('jquery-ui/dist/jquery-ui.min.js');
    await import('./datepicker-pt-BR.js');
    await import('bootstrap/dist/js/bootstrap.bundle.min.js');

    const JSZip = (await import('jszip')).default;
    const pdfMake = (await import('pdfmake/build/pdfmake')).default;
    const pdfFonts = (await import('pdfmake/build/vfs_fonts')).default;

    pdfMake.vfs = pdfFonts.pdfMake ? pdfFonts.pdfMake.vfs : pdfFonts.vfs;
    window.JSZip = JSZip;
    window.pdfMake = pdfMake;

    await import('datatables.net/js/jquery.dataTables.min.js');
    await import('datatables.net-bs4/js/dataTables.bootstrap4.min.js');
    await import('datatables.net-buttons/js/dataTables.buttons.min.js');
    await import('datatables.net-buttons/js/buttons.html5.min.js');
    await import('datatables.net-buttons/js/buttons.print.min.js');
    await import('datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js');
    await import('datatables.net-responsive/js/dataTables.responsive.min.js');
    await import('datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js');

    await import('./app-legacy.js');
    await import('./page-utils.js');
};

load().catch((error) => {
    console.error('Falha ao carregar os assets da aplicação.', error);
});
