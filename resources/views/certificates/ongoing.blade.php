\documentclass[12pt, portuguese, a4paper, pdftex, fleqn]{article}
\usepackage{adjustbox}
\usepackage[portuguese]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage{float}
\usepackage{setspace}
\usepackage[hidelinks]{hyperref}
\usepackage[svgnames,table]{xcolor}
\usepackage{booktabs, makecell, longtable}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{blindtext}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\fancyhead{}
\renewcommand{\headrulewidth}{0pt}
\doublespacing

\begin{document}

\begin{center}
  
\begin{minipage}{0.3\textwidth}
\begin{figure}[H]
 \includegraphics[scale=1.2]{{!! base_path() . "/storage/app/images/logo_ime_completo.jpg" !!}}
\end{figure}
\end{minipage} \hfill
\begin{minipage}{0.2\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.45]{{!! base_path() . "/storage/app/images/logo_usp.jpg" !!}}
\end{figure}
\end{minipage}\\[2cm]
     
   {\fontsize{40}{48} \textbf{ATESTADO}}\\[5cm]

   \end{center}
   \hspace{2cm} Atestamos para os devidos fins que \textbf{{!! $selection->student->nompes !!}}
   é {!! $selection->student->getSexo() == "F" ? "aluna-monitora" : "aluno-monitor" !!}
  do Instituto de Matemática e Estatística da Universidade de São Paulo, sem vínculo
  empregatício, até o final do {!! $selection->schoolclass->schoolterm->period !!} de {!! $selection->schoolclass->schoolterm->year !!}, 
  junto à disciplina “\textbf{{!! $selection->schoolclass->coddis !!} - {!! $selection->schoolclass->nomdis !!}}”, sob a responsabilidade 
  {!! $selection->requisition->instructor->getPronounTreatment() == "Prof. Dr. " ? "do" : "da" !!}
  {!! $selection->requisition->instructor->getPronounTreatment() . $selection->requisition->instructor->nompes !!}.
  \\[3cm]
\begin{flushright}
  @php
    $meses = ["01"=>"janeiro", "02"=>"fevereiro", "03"=>"março", "04"=>"abril", "05"=>"maio", "06"=>"junho", "07"=>"julho", "08"=>"agosto", "09"=>"setembro", "10"=>"outubro", "11"=>"novembro", "12"=>"dezembro"];
  @endphp
  São Paulo, {!! date("d") . " de " . $meses[date("m")] . " de " . date("Y") !!}
\end{flushright}

\hfill
\begin{minipage}{0.3\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.4]{{!! base_path() . "/storage/app/images/ZaraSignature.png" !!}}
\end{figure}
\end{minipage}

\vfill
\begin{figure}[H]
 \includegraphics[scale=0.4]{{!! base_path() . "/storage/app/images/footer.png" !!}}
\end{figure}
\thispagestyle{empty}
\pagebreak
\end{document}
