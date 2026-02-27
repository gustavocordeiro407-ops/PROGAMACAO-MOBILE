algoritmo "ATIVIDADE 02"
var
   i: inteiro
   nota, soma: real
inicio
   soma <- 0
   para i de 1 ate 5 faca
      repita
         escreva("Digite a nota ", i, " (0 a 10): ")
         leia(nota)
      ate (nota >= 0) e (nota <= 10)
      soma <- soma + nota
   fimpara
   escreva("Média: ", soma / 5)
fimalgoritmo