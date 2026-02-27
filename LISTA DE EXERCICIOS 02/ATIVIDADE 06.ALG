algoritmo "ATIVIDADE 06"
var
   n, soma, qtd: real
inicio
   soma <- 0
   qtd <- 0
   escreva("Digite um número (negativo para sair): ")
   leia(n)

   enquanto n >= 0 faca
      soma <- soma + n
      qtd <- qtd + 1
      escreva("Digite um número: ")
      leia(n)
   fimenquanto

   escreval("Soma positivos: ", soma)
   se qtd > 0 entao
      escreval("Média: ", soma / qtd)
   senao
      escreval("Nenhum positivo digitado")
   fimse
fimalgoritmo