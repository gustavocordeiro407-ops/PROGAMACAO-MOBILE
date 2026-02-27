algoritmo "ATIVIDADE 18"
var sal, parc: real
inicio
   escreva("Salário: ")
   leia(sal)
   escreva("Parcela: ")
   leia(parc)
   se parc <= (sal * 0.30) entao
      escreva("Aprovado")
   senao
      escreva("Negado")
   fimse
fimalgoritmo