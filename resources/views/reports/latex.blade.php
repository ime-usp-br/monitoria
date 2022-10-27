\documentclass[12pt, portuguese, a4paper, pdftex, fleqn]{article}
\usepackage{adjustbox}
\usepackage[portuguese]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage{float}
\usepackage{indentfirst}
\usepackage[hidelinks]{hyperref}
\usepackage[svgnames,table]{xcolor}
\usepackage{booktabs, makecell, longtable}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{blindtext}
\usepackage{pdflscape}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\pagestyle{fancy}
\fancyhead{}
\renewcommand{\headrulewidth}{0pt}

\begin{document}
\begin{titlepage}

\begin{center}
  
\begin{minipage}{0.3\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.2]{{!! base_path() . "/storage/app/images/logo_ime.jpg" !!}}
\end{figure}
\end{minipage} \hfill
\begin{minipage}{0.2\textwidth}
\begin{figure}[H]
 \includegraphics[scale=0.55]{{!! base_path() . "/storage/app/images/logo_usp.jpg" !!}}
\end{figure}
\end{minipage}\\[8cm]
     
   {\Large \textbf{Resumo do processo de seleção dos monitores das disciplinas de graduação do IME no {!! $schoolterm->period !!} de {!! $schoolterm->year !!}}}\\[5cm]

   \hspace{.45\textwidth} %posiciona a minipage
  \vfill

\vspace{1cm}


\large \textbf{São Paulo}

\large \textbf{{!! now()->year !!}}

  \end{center}
\thispagestyle{empty}
\pagebreak
\end{titlepage}

\section*{ Visão Global}

No {!! $schoolterm->period !!} de {!! $schoolterm->year !!} foram cadastradas no Sistema Jupiter 
{!! $schoolterm->schoolclasses->count() !!} turmas, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->count() !!} do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->count() !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->count() !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->count() !!} do Departamento de Matemática.

No mesmo semestre foram solicitados {!! $schoolterm->schoolclasses->sum('requisition.requested_number') !!} monitores pelos docentes, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->get()->sum('requisition.requested_number') !!} do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->get()->sum('requisition.requested_number') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->get()->sum('requisition.requested_number') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->get()->sum('requisition.requested_number') !!} do Departamento de Matemática.

Foram feitas {!! $schoolterm->schoolclasses()->withCount('enrollments')->get()->sum('enrollments_count') !!} inscrições nas vagas de monitoria por parte de  
{!! count(App\Models\Student::whereHas('enrollments', function($query) use($schoolterm) {return $query->whereHas('schoolclass', function($query2) use($schoolterm) {return $query2->whereBelongsTo($schoolterm);});})->get()) !!} alunos, sendo 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('enrollments')->get()->sum('enrollments_count') !!}  do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('enrollments')->get()->sum('enrollments_count') !!} do Departamento de Matemática.

Foram selecionados {!! $schoolterm->schoolclasses()->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} monitores no total, sendo
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!}  do Departamento de Ciência da Computação, 
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} do Departamento de Matemática Aplicada,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} do Departamento de Estatística,
{!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} do Departamento de Matemática.

\begin{table}[h]
    \caption{Resumo dos dados gerais.}
    \begin{center}
        \begin{tabular}{ l c c c c c }
            \hline
            & Total & MAC & MAP & MAE & MAT\\
            \hline
            Turmas & {!! $schoolterm->schoolclasses->count() !!} & 
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->count() !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->count() !!} \\
            Vagas solicitadas &
            {!! $schoolterm->schoolclasses->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->get()->sum('requisition.requested_number') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->get()->sum('requisition.requested_number') !!} \\
            Inscrições &
            {!! $schoolterm->schoolclasses()->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('enrollments')->get()->sum('enrollments_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('enrollments')->get()->sum('enrollments_count') !!} \\
            Monitores eleitos &
            {!! $schoolterm->schoolclasses()->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} &
            {!! $schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->withCount('selections')->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get()->sum('selections_count') !!} \\
            \hline
        \end{tabular}
    \end{center}
\end{table}

\pagebreak

@if(App\Models\Selection::whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->where("sitatl","!=","Desligado")->get()->isNotEmpty())
 
\begin{landscape}
\section*{Detalhamento por Departamento}

@if(App\Models\Selection::whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->whereHas('schoolclass.department', function ($query) { return $query->where('nomabvset', 'MAC');})->where("sitatl","!=","Desligado")->get()->isNotEmpty())

\subsection*{Departamento de Ciência da Computação}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm} p{8cm}}
    \caption{Relação de monitores eleitos do Departamento de Ciência da Computação.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Ciência da Computação (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAC');})->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &     
    \href{mailto:{!! $schoolclass->requisition->instructor->codema !!}}{{!! $schoolclass->requisition->instructor->getNomAbrev()!!}}  & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) \href{mailto:{!! $selection->student->codema !!}}{{!! $selection->student->getNomAbrev() !!}} {!! $selection->enrollment->voluntario ? "(Voluntário)" : "" !!}\\ @endforeach} & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) {!! $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" !!}\\ @endforeach} 
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}

\pagebreak
@endif

@if(App\Models\Selection::whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->whereHas('schoolclass.department', function ($query) { return $query->where('nomabvset', 'MAP');})->where("sitatl","!=","Desligado")->get()->isNotEmpty())

\subsection*{Departamento de Matemática Aplicada}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm} p{8cm}}
    \caption{Relação de monitores eleitos do Departamento de Matemática Aplicada.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Matemática Aplicada (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAP');})->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! $schoolclass->requisition->instructor->codema !!}}{{!! $schoolclass->requisition->instructor->getNomAbrev()!!}}  & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) \href{mailto:{!! $selection->student->codema !!}}{{!! $selection->student->getNomAbrev() !!}} {!! $selection->enrollment->voluntario ? "(Voluntário)" : "" !!}\\ @endforeach} & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) {!! $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" !!}\\ @endforeach} 
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}


\pagebreak
@endif

@if(App\Models\Selection::whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->whereHas('schoolclass.department', function ($query) { return $query->where('nomabvset', 'MAE');})->where("sitatl","!=","Desligado")->get()->isNotEmpty())

\subsection*{Departamento de Estatística}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm} p{8cm}}
    \caption{Relação de monitores eleitos do Departamento de Estatística.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Estatística (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAE');})->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! $schoolclass->requisition->instructor->codema !!}}{{!! $schoolclass->requisition->instructor->getNomAbrev()!!}}  & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) \href{mailto:{!! $selection->student->codema !!}}{{!! $selection->student->getNomAbrev() !!}} {!! $selection->enrollment->voluntario ? "(Voluntário)" : "" !!}\\ @endforeach} & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) {!! $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" !!}\\ @endforeach} 
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}

\pagebreak

@endif

@if(App\Models\Selection::whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->whereHas('schoolclass.department', function ($query) { return $query->where('nomabvset', 'MAT');})->where("sitatl","!=","Desligado")->get()->isNotEmpty())

\subsection*{Departamento de Matemática}

\begin{footnotesize}
\begin{longtable}{c  c  p{4cm}  p{4cm} p{4cm} p{8cm}}
    \caption{Relação de monitores eleitos do Departamento de Matemática.}\\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código \\da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endfirsthead
    \caption[]{Relação de monitores eleitos do Departamento de Matemática (cont.).}    \\
    \toprule
    \makecell[b]{Sigla\\ da \\Disciplina}
        &   \makecell[b]{Código\\ da \\Turma} 
        &   \makecell[b]{Nome da Disciplina}
        &   \makecell[b]{Professor(a) Solicitante}
        &   \makecell[b]{Monitores Eleitos}  
        &   \makecell[b]{Curso}  \\
    \midrule
\endhead
    \multicolumn{5}{r}{\footnotesize\itshape Continua na próxima página}
\endfoot
\endlastfoot

@foreach($schoolterm->schoolclasses()->whereHas('department', function ($query) { return $query->where('nomabvset', 'MAT');})->whereHas("selections", function($query){$query->where("sitatl","!=","Desligado");})->get() as $schoolclass)
    {!! $schoolclass->coddis !!} & 
    {!! $schoolclass->codtur !!} & 
    {!! $schoolclass->nomdis !!} &   
    \href{mailto:{!! $schoolclass->requisition->instructor->codema !!}}{{!! $schoolclass->requisition->instructor->getNomAbrev()!!}}  & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) \href{mailto:{!! $selection->student->codema !!}}{{!! $selection->student->getNomAbrev() !!}} {!! $selection->enrollment->voluntario ? "(Voluntário)" : "" !!}\\ @endforeach} & 
    \makecell[l]{@foreach($schoolclass->selections()->where("sitatl","!=","Desligado")->get() as $selection) {!! $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" !!}\\ @endforeach} 
    \\ 
    \midrule
@endforeach
\end{longtable}
\end{footnotesize}
@endif
\end{landscape}
@endif

\end{document}
