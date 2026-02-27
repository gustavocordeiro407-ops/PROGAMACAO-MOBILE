algoritmo "ATIVIDADE 23"
var n, soma: real
    i: inteiro
inicio
   soma <- 0
   para i de 1 ate 5 faca
      escreva("Digite valor ", i, ": ")
      leia(n)
      soma <- soma + n
   fimpara
   escreva("Média: ", soma / 5)
fimalgoritmo