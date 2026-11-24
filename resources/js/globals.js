import $ from 'jquery';

window.$ = window.jQuery = $;
window.baseURL = window.location.origin;

const load = async () => {
    await import('jquery-ui/ui/widgets/datepicker');
    await import('./datepicker-pt-BR.js');
    await import('jquery-ui/ui/widgets/menu');
    await import('jquery-ui/ui/widgets/progressbar');
    await import('bootstrap/dist/js/bootstrap.bundle.min.js');

    const JSZip = (await import('jszip')).default;
    const pdfMake = (await import('pdfmake/build/pdfmake')).default;
    const pdfFonts = (await import('pdfmake/build/vfs_fonts')).default;

    pdfMake.vfs = pdfFonts.pdfMake ? pdfFonts.pdfMake.vfs : pdfFonts.vfs;
    window.JSZip = JSZip;
    window.pdfMake = pdfMake;

    await import('datatables.net');
    await import('datatables.net-bs4');
    await import('datatables.net-buttons');
    await import('datatables.net-buttons-bs4');
    await import('datatables.net-responsive');
    await import('datatables.net-responsive-bs4');

    await import('./app-legacy.js');
    await import('./page-utils.js');
};

load().catch((error) => {
    console.error('Falha ao carregar os assets da aplicação.', error);
});
